class MusicPlayer {
  constructor() {
    this.audio = new Audio();
    this.songs = [];
    this.currentSongIndex = 0;
    this.isPlaying = false;
    this.isDragging = false;
    this.isShuffle = false;
    this.repeatMode = 0;

    this.initElements();
    this.loadSongs();
    this.bindEvents();
  }

  initElements() {
    this.$rewindBtn = jQuery(".rewind");
    this.$fastForwardBtn = jQuery(".fast-forward");
    this.$playPauseBtn = jQuery(".play-pause");
    this.$nextBtn = jQuery(".next");
    this.$prevBtn = jQuery(".prev");
    this.$shuffleBtn = jQuery(".shuffle");
    this.$repeatBtn = jQuery(".repeat");
    this.$backBtn = jQuery(".back-to-archive");
    this.$volumeSlider = jQuery(".volume-slider");
    this.$seekbar = jQuery(".seekbar");
    this.$playlistItems = jQuery(".playlist_item");
    this.$coverArt = jQuery(".cover-art");
    this.$songTitle = jQuery(".song-info .song-title");
    this.$songArtist = jQuery(".song-info .song-artist");
    this.$songAlbum = jQuery(".song-info .song-album");
    this.$songExcerpt = jQuery(".song-info .song-excerpt");
    this.$songDescription = jQuery(".song-info .song-description");
    // برای نمایش تعداد آهنگ‌ها: اضافه کردن song-position
    this.$songPosition = jQuery(".song-info .song-position");
    this.$sidebarToggle = jQuery(".sidebar-toggle");
    this.$sidebar = jQuery(".sidebar");
  }

  bindEvents() {
    this.$rewindBtn.on("click", () => this.rewind15Seconds());
    this.$fastForwardBtn.on("click", () => this.fastForward15Seconds());
    this.$playPauseBtn.on("click", () => this.togglePlayPause());
    this.$nextBtn.on("click", () => this.nextSong());
    this.$prevBtn.on("click", () => this.prevSong());
    this.$shuffleBtn.on("click", () => this.toggleShuffle());
    this.$repeatBtn.on("click", () => this.toggleRepeat());
    this.$backBtn.on(
      "click",
      () => (window.location.href = tunetales_vars.archive_url)
    );
    this.$volumeSlider.on("input", (e) => this.setVolume(e.target.value));
    this.$seekbar.on("input", (e) => this.seek(e.target.value));
    this.$seekbar.on("change", () => {
      this.isDragging = false;
    });
    this.$playlistItems.on("click", (e) => {
      if (!jQuery(e.target).hasClass("download-song")) {
        this.playFromList(jQuery(e.currentTarget).index());
      }
    });
    this.$sidebarToggle.on("click", (e) => {
      e.stopPropagation();
      this.toggleSidebar();
    });

    jQuery(document).on("click", (e) => {
      if (
        this.$sidebar.hasClass("active") &&
        !this.$sidebar.is(e.target) &&
        this.$sidebar.has(e.target).length === 0 &&
        !this.$sidebarToggle.is(e.target)
      ) {
        this.$sidebar.removeClass("active");
      }
    });

    // برای پشتیبانی از کیبورد: اضافه کردن event listener برای keydown
    jQuery(document).on("keydown", (e) => {
      // جلوگیری از رفتار پیش‌فرض برای کلیدهای خاص
      if (
        ["Space", "ArrowRight", "ArrowLeft", "ArrowUp", "ArrowDown"].includes(
          e.code
        )
      ) {
        e.preventDefault();
      }

      switch (e.code) {
        case "Space": // Play/Pause
          this.togglePlayPause();
          this.$playPauseBtn.focus();
          break;
        case "ArrowRight": // Next
          this.nextSong();
          this.$nextBtn.focus();
          break;
        case "ArrowLeft": // Previous
          this.prevSong();
          this.$prevBtn.focus();
          break;
        case "KeyS": // Shuffle
          this.toggleShuffle();
          this.$shuffleBtn.focus();
          break;
        case "KeyR": // Repeat
          this.toggleRepeat();
          this.$repeatBtn.focus();
          break;
        case "ArrowUp": // Fast Forward 15 seconds
          this.fastForward15Seconds();
          this.$fastForwardBtn.focus();
          break;
        case "ArrowDown": // Rewind 15 seconds
          this.rewind15Seconds();
          this.$rewindBtn.focus();
          break;
        case "KeyF": // Fast Forward 15 seconds
          this.fastForward15Seconds();
          this.$fastForwardBtn.focus();
          break;
        case "KeyB": // Rewind 15 seconds
          this.rewind15Seconds();
          this.$rewindBtn.focus();
          break;
      }
    });

    // برای پشتیبانی از کیبورد: انتخاب آهنگ از لیست با Enter
    this.$playlistItems.on("keydown", (e) => {
      if (e.code === "Enter") {
        this.playFromList(jQuery(e.currentTarget).index());
      }
    });

    this.audio.addEventListener("loadedmetadata", () => this.updateMetadata());
    this.audio.addEventListener("timeupdate", () => this.updateTime());
    this.audio.addEventListener("ended", () => this.handleSongEnd());
    this.audio.addEventListener("progress", () => this.updateBuffering());
  }

  toggleSidebar() {
    this.$sidebar.toggleClass("active");
  }

  loadSongs() {
    this.$playlistItems.each((_, item) => {
      const src = jQuery(item).data("src");
      if (src) this.songs.push(src);
    });
    if (this.songs.length > 0) this.updateUI();
  }

  playSong(index) {
    if (index < 0 || index >= this.songs.length) return;
    this.currentSongIndex = index;
    this.audio.src = this.songs[index];
    this.audio.load();
    this.audio
      .play()
      .catch((error) => console.error("Error playing audio:", error));
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
        this.playSong(this.currentSongIndex);
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
    if (this.isShuffle && this.isPlaying) this.nextSong();
  }

  toggleRepeat() {
    this.repeatMode = (this.repeatMode + 1) % 3;
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
      this.audio.currentTime = 0;
      this.audio
        .play()
        .catch((error) => console.error("Error repeating song:", error));
    } else if (this.repeatMode === 2) {
      this.nextSong();
    } else if (this.isShuffle) {
      this.nextSong();
    } else {
      if (this.currentSongIndex + 1 < this.songs.length) {
        this.nextSong();
      } else {
        this.pauseSong();
        this.currentSongIndex = 0;
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

    const songData = this.$playlistItems.eq(this.currentSongIndex);
    const title = songData.data("title") || "Unknown Title";
    const artist = songData.data("artist") || "Unknown Artist";
    const album = songData.data("album") || "Unknown Album";
    const excerpt = songData.data("excerpt") || "";
    const description = songData.data("description") || "";
    this.$songTitle.text(title);
    this.$songArtist.text(artist);
    this.$songAlbum.text(album);
    this.$songExcerpt.text(excerpt);
    this.$songDescription.text(description);
    // برای نمایش تعداد آهنگ‌ها: آپدیت song-position
    this.$songPosition.text(
      `Song ${this.currentSongIndex + 1} of ${this.songs.length}`
    );

    const src = songData.data("src");
    const defaultCover = tunetales_vars.plugin_url + "/default-cover.jpg";
    if (!src) {
      console.error("Song source is empty");
      // برای انیمیشن تغییر آهنگ: اعمال fade
      this.$coverArt.removeClass("fade");
      this.$coverArt.attr("src", defaultCover);
      this.$coverArt.addClass("fade");
      return;
    }

    console.log("Sending AJAX request with src:", src);

    jQuery.ajax({
      url: tunetales_vars.ajaxurl,
      method: "POST",
      data: {
        action: "get_attachment_id",
        url: src,
        nonce: tunetales_vars.nonce,
      },
      success: (response) => {
        console.log("AJAX response for get_attachment_id:", response);
        if (response.success) {
          const attachmentId = response.data.id || 0;
          if (attachmentId) {
            jQuery.ajax({
              url: tunetales_vars.ajaxurl,
              method: "POST",
              data: {
                action: "get_attachment_url",
                id: attachmentId,
                size: "medium",
                nonce: tunetales_vars.nonce,
              },
              success: (response) => {
                console.log("AJAX response for get_attachment_url:", response);
                if (response.success) {
                  // برای انیمیشن تغییر آهنگ: اعمال fade
                  this.$coverArt.removeClass("fade");
                  this.$coverArt.attr("src", response.data.url || defaultCover);
                  this.$coverArt.addClass("fade");
                } else {
                  console.error(
                    "Error fetching attachment URL:",
                    response.data.message
                  );
                  this.$coverArt.removeClass("fade");
                  this.$coverArt.attr("src", defaultCover);
                  this.$coverArt.addClass("fade");
                }
              },
              error: (xhr, status, error) => {
                console.error("Error fetching attachment URL:", status, error);
                this.$coverArt.removeClass("fade");
                this.$coverArt.attr("src", defaultCover);
                this.$coverArt.addClass("fade");
              },
            });
          } else {
            this.$coverArt.removeClass("fade");
            this.$coverArt.attr("src", defaultCover);
            this.$coverArt.addClass("fade");
          }
        } else {
          console.error("Error fetching attachment ID:", response.data.message);
          this.$coverArt.removeClass("fade");
          this.$coverArt.attr("src", defaultCover);
          this.$coverArt.addClass("fade");
        }
      },
      error: (xhr, status, error) => {
        console.error("Error fetching attachment ID:", status, error);
        this.$coverArt.removeClass("fade");
        this.$coverArt.attr("src", defaultCover);
        this.$coverArt.addClass("fade");
      },
    });
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
  rewind15Seconds() {
    const newTime = Math.max(0, this.audio.currentTime - 15);
    this.audio.currentTime = newTime;
    this.updateTime();
  }

  fastForward15Seconds() {
    const newTime = Math.min(this.audio.duration, this.audio.currentTime + 15);
    this.audio.currentTime = newTime;
    this.updateTime();
  }
}

jQuery(document).ready(() => new MusicPlayer());
