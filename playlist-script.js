class MusicPlayer {
  constructor() {
    this.audio = new Audio();
    this.songs = [];
    this.currentSongIndex = 0;
    this.isPlaying = false;
    this.isDragging = false;
    this.isShuffle = false;

    this.initElements();
    this.loadSongs();
    this.bindEvents();
  }

  initElements() {
    this.$playPauseBtn = jQuery(".play-pause");
    this.$nextBtn = jQuery(".next");
    this.$prevBtn = jQuery(".prev");
    this.$shuffleBtn = jQuery(".shuffle");
    this.$volumeSlider = jQuery(".volume-slider");
    this.$seekbar = jQuery(".seekbar");
    this.$playlistItems = jQuery(".playlist_item");
  }

  loadSongs() {
    this.$playlistItems.each((_, item) => {
      this.songs.push(jQuery(item).data("src"));
    });
  }

  bindEvents() {
    this.$playPauseBtn.on("click", () => this.togglePlayPause());
    this.$nextBtn.on("click", () => this.nextSong());
    this.$prevBtn.on("click", () => this.prevSong());
    this.$shuffleBtn.on("click", () => this.toggleShuffle());
    this.$volumeSlider.on("input", (e) => this.setVolume(e.target.value));
    this.$seekbar.on("input", (e) => this.seek(e.target.value));
    this.$seekbar.on("change", () => {
      this.isDragging = false;
    });
    this.$playlistItems.on("click", (e) =>
      this.playFromList(jQuery(e.currentTarget).index())
    );

    this.audio.addEventListener("loadedmetadata", () => this.updateMetadata());
    this.audio.addEventListener("timeupdate", () => this.updateTime());
    this.audio.addEventListener("ended", () => this.handleSongEnd());
    this.audio.addEventListener("progress", () => this.updateBuffering());
  }

  playSong(index) {
    if (index < 0 || index >= this.songs.length) return;

    // اگه آهنگ عوض شده، منبع رو تنظیم کن
    if (this.currentSongIndex !== index || !this.audio.src) {
      this.currentSongIndex = index;
      this.audio.src = this.songs[index];
      this.audio.load(); // بارگذاری آهنگ جدید
    }

    this.audio.play();
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
      // اگه آهنگ قبلاً انتخاب شده و فقط پاز شده، از همونجا ادامه بده
      if (this.audio.src) {
        this.audio.play();
        this.isPlaying = true;
        this.updateUI();
      } else {
        // اگه هنوز آهنگی انتخاب نشده، از آهنگ فعلی شروع کن
        this.playSong(this.currentSongIndex);
      }
    }
  }

  nextSong() {
    this.currentSongIndex = this.isShuffle
      ? this.getRandomIndex()
      : (this.currentSongIndex + 1) % this.songs.length;
    this.playSong(this.currentSongIndex);
  }

  prevSong() {
    this.currentSongIndex = this.isShuffle
      ? this.getRandomIndex()
      : (this.currentSongIndex - 1 + this.songs.length) % this.songs.length;
    this.playSong(this.currentSongIndex);
  }

  toggleShuffle() {
    if (this.songs.length <= 1) {
      alert("Shuffle mode requires more than one song.");
      return;
    }
    this.isShuffle = !this.isShuffle;
    this.$shuffleBtn.toggleClass("active");
    if (this.isShuffle) this.nextSong();
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
    this.currentSongIndex = index;
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

  handleSongEnd() {
    this.nextSong();
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
