class MusicPlayer {
  constructor() {
    this.audio = new Audio();
    this.songs = [];
    this.currentSongIndex = 0;
    this.isPlaying = false;
    this.isDragging = false;
    this.isShuffle = false;
    this.repeatMode = 0; // 0: خاموش، 1: تکرار آهنگ فعلی، 2: تکرار پلی‌لیست

    this.initElements();
    this.loadSongs();
    this.bindEvents();
  }

  initElements() {
    this.$playPauseBtn = jQuery(".play-pause");
    this.$nextBtn = jQuery(".next");
    this.$prevBtn = jQuery(".prev");
    this.$shuffleBtn = jQuery(".shuffle");
    this.$repeatBtn = jQuery(".repeat");
    this.$volumeSlider = jQuery(".volume-slider");
    this.$seekbar = jQuery(".seekbar");
    this.$playlistItems = jQuery(".playlist_item");
  }

  loadSongs() {
    this.$playlistItems.each((_, item) => {
      const src = jQuery(item).data("src");
      if (src) this.songs.push(src);
    });
  }

  bindEvents() {
    this.$playPauseBtn.on("click", () => this.togglePlayPause());
    this.$nextBtn.on("click", () => this.nextSong());
    this.$prevBtn.on("click", () => this.prevSong());
    this.$shuffleBtn.on("click", () => this.toggleShuffle());
    this.$repeatBtn.on("click", () => this.toggleRepeat());
    this.$volumeSlider.on("input", (e) => this.setVolume(e.target.value));
    this.$seekbar.on("input", (e) => this.seek(e.target.value));
    this.$seekbar.on("change", () => {
      this.isDragging = false;
    });
    // فقط روی خود آیتم کلیک کنه، نه لینک دانلود
    this.$playlistItems.on("click", (e) => {
      if (!jQuery(e.target).hasClass("download-song")) {
        this.playFromList(jQuery(e.currentTarget).index());
      }
    });

    this.audio.addEventListener("loadedmetadata", () => this.updateMetadata());
    this.audio.addEventListener("timeupdate", () => this.updateTime());
    this.audio.addEventListener("ended", () => this.handleSongEnd());
    this.audio.addEventListener("progress", () => this.updateBuffering());
  }

  playSong(index) {
    if (index < 0 || index >= this.songs.length) return;

    this.currentSongIndex = index;
    this.audio.src = this.songs[index]; // همیشه منبع جدید رو ست کن
    this.audio.load(); // لود منبع جدید
    this.audio
      .play()
      .catch((error) => console.error("Error playing audio:", error)); // پخش با مدیریت خطا
    this.isPlaying = true;
    this.updateUI();
  }

  pauseSong() {
    this.audio.pause();
    this.isPlaying = false;
    this.updateUI();
  }

  togglePlayPause() {
    if (this.isPlaying) {
      this.pauseSong();
    } else {
      if (!this.audio.src) {
        this.playSong(this.currentSongIndex); // اگه منبعی نیست، آهنگ اول رو پخش کن
      } else {
        this.audio
          .play()
          .catch((error) => console.error("Error resuming audio:", error));
        this.isPlaying = true;
        this.updateUI();
      }
    }
  }

  nextSong() {
    if (this.isShuffle) {
      this.currentSongIndex = this.getRandomIndex();
    } else {
      this.currentSongIndex = (this.currentSongIndex + 1) % this.songs.length;
    }
    this.playSong(this.currentSongIndex);
  }

  prevSong() {
    if (this.isShuffle) {
      this.currentSongIndex = this.getRandomIndex();
    } else {
      this.currentSongIndex =
        (this.currentSongIndex - 1 + this.songs.length) % this.songs.length;
    }
    this.playSong(this.currentSongIndex);
  }

  toggleShuffle() {
    if (this.songs.length <= 1) {
      alert("Shuffle mode requires more than one song.");
      return;
    }
    this.isShuffle = !this.isShuffle;
    this.$shuffleBtn.toggleClass("active");
    if (this.isShuffle && this.isPlaying) {
      this.nextSong(); // وقتی شافل فعاله، یه آهنگ رندوم پخش کن
    }
  }

  toggleRepeat() {
    this.repeatMode = (this.repeatMode + 1) % 3; // 0 -> 1 -> 2 -> 0
    this.$repeatBtn.removeClass("active repeat-one repeat-all");
    if (this.repeatMode === 1) {
      this.$repeatBtn.addClass("active repeat-one");
      this.$repeatBtn.attr("title", "Repeat One");
    } else if (this.repeatMode === 2) {
      this.$repeatBtn.addClass("active repeat-all");
      this.$repeatBtn.attr("title", "Repeat All");
    } else {
      this.$repeatBtn.attr("title", "Repeat Off");
    }
  }

  handleSongEnd() {
    if (this.repeatMode === 1) {
      this.audio.currentTime = 0; // تکرار آهنگ فعلی
      this.audio
        .play()
        .catch((error) => console.error("Error repeating song:", error));
    } else if (this.repeatMode === 2) {
      this.nextSong(); // تکرار پلی‌لیست
    } else if (this.isShuffle) {
      this.nextSong(); // شافل
    } else {
      // حالت خاموش: اگه به آخر رسید، متوقف کن
      if (this.currentSongIndex + 1 < this.songs.length) {
        this.nextSong();
      } else {
        this.pauseSong();
        this.currentSongIndex = 0; // برگرد به اول، ولی پخش نکن
        this.updateUI();
      }
    }
  }

  setVolume(value) {
    this.audio.volume = parseFloat(value);
    jQuery(".volume-display").text(Math.round(value * 100));
  }

  seek(value) {
    this.isDragging = true;
    this.audio.currentTime = value;
    jQuery(".current-time").text(this.formatTime(value));
  }

  playFromList(index) {
    this.playSong(index);
  }

  updateUI() {
    const iconClass = this.isPlaying ? "fa-pause" : "fa-play";
    this.$playPauseBtn
      .find("i")
      .removeClass("fa-play fa-pause")
      .addClass(iconClass);
    this.$playlistItems
      .removeClass("playing")
      .eq(this.currentSongIndex)
      .addClass("playing");
  }

  updateMetadata() {
    this.$seekbar.attr("max", this.audio.duration);
    jQuery(".duration-time").text(this.formatTime(this.audio.duration));
  }

  updateTime() {
    if (!this.isDragging) {
      const progress =
        (this.audio.currentTime / this.audio.duration) * 100 || 0;
      this.$seekbar.val(this.audio.currentTime);
      this.$seekbar.css("--value", `${progress}%`);
      jQuery(".current-time").text(this.formatTime(this.audio.currentTime));
    }
  }

  updateBuffering() {
    if (this.audio.buffered.length > 0) {
      const bufferedEnd = this.audio.buffered.end(
        this.audio.buffered.length - 1
      );
      const percentage = (bufferedEnd / this.audio.duration) * 100;
      jQuery(".buffering-bar").css("width", `${percentage}%`);
    }
  }

  getRandomIndex() {
    let nextIndex;
    do {
      nextIndex = Math.floor(Math.random() * this.songs.length);
    } while (nextIndex === this.currentSongIndex);
    return nextIndex;
  }

  formatTime(seconds) {
    const minutes = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${minutes < 10 ? "0" : ""}${minutes}:${
      secs < 10 ? "0" : ""
    }${secs}`;
  }
}

jQuery(document).ready(() => new MusicPlayer());
