/**
 * کلاس MusicPlayer برای مدیریت پخش‌کننده موسیقی TuneTales
 */
class MusicPlayer {
  /**
   * سازنده کلاس، برای مقداردهی اولیه متغیرها و راه‌اندازی پخش‌کننده
   */
  constructor() {
    this.audio = new Audio(); // ایجاد نمونه‌ای از شیء Audio برای پخش موسیقی
    this.songs = []; // آرایه برای ذخیره لیست آهنگ‌ها
    this.currentSongIndex = 0; // شاخص آهنگ فعلی
    this.isPlaying = false; // وضعیت پخش (در حال پخش یا متوقف)
    this.isDragging = false; // وضعیت درگ کردن نوار پخش
    this.isShuffle = false; // وضعیت حالت تصادفی
    this.repeatMode = 0; // حالت تکرار (0: خاموش، 1: تکرار تک آهنگ، 2: تکرار همه)

    this.initElements(); // مقداردهی اولیه عناصر DOM
    this.loadSongs(); // بارگذاری لیست آهنگ‌ها
    this.bindEvents(); // اتصال رویدادها به عناصر
  }

  /**
   * مقداردهی اولیه عناصر DOM با استفاده از jQuery
   */
  initElements() {
    this.$rewindBtn = jQuery(".rewind"); // دکمه عقب‌گرد 15 ثانیه
    this.$fastForwardBtn = jQuery(".fast-forward"); // دکمه جلو 15 ثانیه
    this.$playPauseBtn = jQuery(".play-pause"); // دکمه پخش/توقف
    this.$nextBtn = jQuery(".next"); // دکمه آهنگ بعدی
    this.$prevBtn = jQuery(".prev"); // دکمه آهنگ قبلی
    this.$shuffleBtn = jQuery(".shuffle"); // دکمه حالت تصادفی
    this.$repeatBtn = jQuery(".repeat"); // دکمه تکرار
    this.$backBtn = jQuery(".back-to-archive"); // دکمه بازگشت به آرشیو
    this.$volumeSlider = jQuery(".volume-slider"); // اسلایدر ولوم
    this.$seekbar = jQuery(".seekbar"); // نوار پخش
    this.$playlistItems = jQuery(".playlist_item"); // آیتم‌های پلی‌لیست
    this.$coverArt = jQuery(".cover-art"); // تصویر کاور آهنگ
    this.$songTitle = jQuery(".song-info .song-title"); // عنوان آهنگ
    this.$songArtist = jQuery(".song-info .song-artist"); // نام هنرمند
    this.$songAlbum = jQuery(".song-info .song-album"); // نام آلبوم
    this.$songExcerpt = jQuery(".song-info .song-excerpt"); // توضیح کوتاه
    this.$songDescription = jQuery(".song-info .song-description"); // توضیحات
    this.$songPosition = jQuery(".song-info .song-position"); // موقعیت آهنگ
    this.$currentTime = jQuery(".current-time"); // زمان فعلی آهنگ
    this.$durationTime = jQuery(".duration-time"); // زمان کل آهنگ
    this.$volumeDisplay = jQuery(".volume-display"); // نمایش درصد ولوم
    this.$sidebarToggle = jQuery(".sidebar-toggle"); // دکمه تغییر وضعیت سایدبار (قبلی)
    this.$playlistToggle = jQuery(".playlist-toggle"); // دکمه جدید تغییر وضعیت پلی‌لیست
    this.$sidebar = jQuery(".sidebar"); // سایدبار پلی‌لیست
  }

  /**
   * اتصال رویدادها به عناصر DOM
   */
  bindEvents() {
    // رویداد کلیک برای دکمه عقب‌گرد 15 ثانیه
    if (this.$rewindBtn?.length) {
      this.$rewindBtn.on("click", () => {
        this.rewind15Seconds();
      });
    }

    // رویداد کلیک برای دکمه جلو 15 ثانیه
    if (this.$fastForwardBtn?.length) {
      this.$fastForwardBtn.on("click", () => {
        this.fastForward15Seconds();
      });
    }

    // رویداد کلیک برای دکمه پخش/توقف
    if (this.$playPauseBtn?.length) {
      this.$playPauseBtn.on("click", () => {
        this.togglePlayPause();
      });
    }

    // رویداد کلیک برای دکمه آهنگ بعدی
    if (this.$nextBtn?.length) {
      this.$nextBtn.on("click", () => {
        this.nextSong();
      });
    }

    // رویداد کلیک برای دکمه آهنگ قبلی
    if (this.$prevBtn?.length) {
      this.$prevBtn.on("click", () => {
        this.prevSong();
      });
    }

    // رویداد کلیک برای دکمه حالت تصادفی
    if (this.$shuffleBtn?.length) {
      this.$shuffleBtn.on("click", () => {
        this.toggleShuffle();
      });
    }

    // رویداد کلیک برای دکمه تکرار
    if (this.$repeatBtn?.length) {
      this.$repeatBtn.on("click", () => {
        this.toggleRepeat();
      });
    }

    // رویداد کلیک برای دکمه بازگشت به آرشیو
    if (this.$backBtn?.length) {
      this.$backBtn.on("click", () => {
        window.location.href = tunetales_vars?.archive_url || "/";
      });
    }

    // رویداد تغییر برای اسلایدر ولوم
    if (this.$volumeSlider?.length) {
      this.$volumeSlider.on("input", (e) => {
        this.setVolume(e.target.value);
      });
    }

    // رویدادهای نوار پخش
    if (this.$seekbar?.length) {
      this.$seekbar.on("input", (e) => {
        this.seek(e.target.value);
      });
      this.$seekbar.on("change", () => {
        this.isDragging = false;
      });
    }

    // رویداد کلیک و کیبورد برای آیتم‌های پلی‌لیست
    if (this.$playlistItems?.length) {
      this.$playlistItems.on("click", (e) => {
        if (!jQuery(e.target).hasClass("download-song")) {
          this.playFromList(jQuery(e.currentTarget).index());
        }
      });
      this.$playlistItems.on("keydown", (e) => {
        if (e.code === "Enter") {
          this.playFromList(jQuery(e.currentTarget).index());
        }
      });
    }

    // رویداد کلیک برای دکمه تغییر وضعیت سایدبار (قبلی)
    if (this.$sidebarToggle?.length) {
      this.$sidebarToggle.on("click", (e) => {
        e.stopPropagation();
        this.toggleSidebar();
      });
    }

    // رویداد کلیک برای دکمه جدید تغییر وضعیت پلی‌لیست
    if (this.$playlistToggle?.length) {
      this.$playlistToggle.on("click", (e) => {
        e.stopPropagation();
        this.toggleSidebar();
      });
    }

    // بستن سایدبار با کلیک خارج از آن
    if (
      this.$sidebar?.length &&
      (this.$sidebarToggle?.length || this.$playlistToggle?.length)
    ) {
      jQuery(document).on("click", (e) => {
        if (
          this.$sidebar.hasClass("active") &&
          !this.$sidebar.is(e.target) &&
          this.$sidebar.has(e.target).length === 0 &&
          !this.$sidebarToggle.is(e.target) &&
          !this.$playlistToggle.is(e.target)
        ) {
          this.$sidebar.removeClass("active");
          if (this.$sidebarToggle?.length) {
            this.$sidebarToggle.attr("aria-expanded", "false");
          }
          if (this.$playlistToggle?.length) {
            this.$playlistToggle.attr("aria-expanded", "false");
          }
        }
      });
    }

    // رویدادهای کیبورد برای کنترل پخش‌کننده
    jQuery(document).on("keydown", (e) => {
      if (
        [
          "Space",
          "ArrowRight",
          "ArrowLeft",
          "ArrowUp",
          "ArrowDown",
          "KeyS",
          "KeyR",
          "KeyF",
          "KeyB",
        ].includes(e.code)
      ) {
        e.preventDefault();
      }

      switch (e.code) {
        case "Space":
          this.togglePlayPause();
          if (this.$playPauseBtn?.length) this.$playPauseBtn.focus();
          break;
        case "ArrowRight":
          this.nextSong();
          if (this.$nextBtn?.length) this.$nextBtn.focus();
          break;
        case "ArrowLeft":
          this.prevSong();
          if (this.$prevBtn?.length) this.$prevBtn.focus();
          break;
        case "KeyS":
          this.toggleShuffle();
          if (this.$shuffleBtn?.length) this.$shuffleBtn.focus();
          break;
        case "KeyR":
          this.toggleRepeat();
          if (this.$repeatBtn?.length) this.$repeatBtn.focus();
          break;
        case "ArrowUp":
        case "KeyF":
          this.fastForward15Seconds();
          if (this.$fastForwardBtn?.length) this.$fastForwardBtn.focus();
          break;
        case "ArrowDown":
        case "KeyB":
          this.rewind15Seconds();
          if (this.$rewindBtn?.length) this.$rewindBtn.focus();
          break;
      }
    });

    // رویدادهای صوتی
    this.audio.addEventListener("loadedmetadata", () => {
      this.updateMetadata(); // به‌روزرسانی متادیتا هنگام بارگذاری
    });
    this.audio.addEventListener("timeupdate", () => {
      this.updateTime(); // به‌روزرسانی زمان هنگام پخش
    });
    this.audio.addEventListener("ended", () => {
      this.handleSongEnd(); // مدیریت پایان آهنگ
    });
  }

  /**
   * تغییر وضعیت نمایش سایدبار با انیمیشن
   */
  toggleSidebar() {
    if (this.$sidebar?.length) {
      this.$sidebar.toggleClass("active");
      const isActive = this.$sidebar.hasClass("active");
      // به‌روزرسانی ویژگی aria-expanded برای هر دو دکمه
      if (this.$sidebarToggle?.length) {
        this.$sidebarToggle.attr("aria-expanded", isActive);
      }
      if (this.$playlistToggle?.length) {
        this.$playlistToggle.attr("aria-expanded", isActive);
      }
    }
  }

  /**
   * بارگذاری لیست آهنگ‌ها از آیتم‌های پلی‌لیست
   */
  loadSongs() {
    this.songs = [];
    if (this.$playlistItems?.length) {
      this.$playlistItems.each((index, item) => {
        const $item = jQuery(item);
        const attachmentId = parseInt($item.data("attachment-id"));
        const songData = $item.data();
        if (attachmentId && songData.src) {
          this.songs.push({
            id: attachmentId,
            src: songData.src || "",
            title: songData.title || "Unknown Title",
            artist: songData.artist || "Unknown Artist",
            album: songData.album || "Unknown Album",
            description: songData.description || "",
            excerpt: songData.excerpt || "",
          });
        } else {
          console.warn("TuneTales: Skipping invalid playlist item:", {
            index,
            attachmentId,
            src: songData.src,
          });
        }
      });
    }
    if (this.songs.length > 0) {
      this.updateUI(); // به‌روزرسانی رابط کاربری پس از بارگذاری آهنگ‌ها
    } else {
      console.warn("TuneTales: No songs found in playlist");
    }
  }

  /**
   * پخش آهنگ با شاخص مشخص
   * @param {number} index - شاخص آهنگ در آرایه songs
   */
  playSong(index) {
    if (index < 0 || index >= this.songs.length || !this.songs.length) {
      console.warn("TuneTales: Invalid song index or no songs", index);
      return;
    }
    this.currentSongIndex = index;
    const song = this.songs[index];
    this.audio.src = song.src;
    this.audio.load();
    this.audio
      .play()
      .then(() => {
        this.isPlaying = true;
        this.updateUI(); // به‌روزرسانی رابط کاربری هنگام پخش
      })
      .catch((error) => {
        console.error("TuneTales: Error playing audio", error);
      });
  }

  /**
   * توقف پخش آهنگ
   */
  pauseSong() {
    if (this.audio.src) {
      this.audio.pause();
      this.isPlaying = false;
      this.updateUI(); // به‌روزرسانی رابط کاربری هنگام توقف
    }
  }

  /**
   * تغییر وضعیت پخش/توقف
   */
  togglePlayPause() {
    if (!this.songs.length) {
      console.warn("TuneTales: No songs to play");
      return;
    }
    if (this.isPlaying) {
      this.pauseSong();
    } else {
      if (!this.audio.src) {
        this.playSong(this.currentSongIndex);
      } else {
        this.audio
          .play()
          .then(() => {
            this.isPlaying = true;
            this.updateUI();
          })
          .catch((error) => {
            console.error("TuneTales: Error resuming audio", error);
          });
      }
    }
  }

  /**
   * پخش آهنگ بعدی
   */
  nextSong() {
    if (!this.songs.length) {
      console.warn("TuneTales: No songs to play next");
      return;
    }
    if (this.isShuffle) {
      this.currentSongIndex = this.getRandomIndex();
    } else {
      this.currentSongIndex = (this.currentSongIndex + 1) % this.songs.length;
    }
    this.playSong(this.currentSongIndex);
  }

  /**
   * پخش آهنگ قبلی
   */
  prevSong() {
    if (!this.songs.length) {
      console.warn("TuneTales: No songs to play previous");
      return;
    }
    if (this.isShuffle) {
      this.currentSongIndex = this.getRandomIndex();
    } else {
      this.currentSongIndex =
        (this.currentSongIndex - 1 + this.songs.length) % this.songs.length;
    }
    this.playSong(this.currentSongIndex);
  }

  /**
   * تغییر وضعیت حالت تصادفی
   */
  toggleShuffle() {
    if (this.songs.length <= 1) {
      console.warn("TuneTales: Shuffle requires more than one song");
      if (this.$shuffleBtn?.length) this.$shuffleBtn.removeClass("active");
      return;
    }
    this.isShuffle = !this.isShuffle;
    this.updateUI();
    if (this.isShuffle && this.isPlaying) {
      this.nextSong();
    }
  }

  /**
   * تغییر حالت تکرار (خاموش، تک آهنگ، همه)
   */
  toggleRepeat() {
    this.repeatMode = (this.repeatMode + 1) % 3;
    this.updateUI();
  }

  /**
   * مدیریت پایان آهنگ
   */
  handleSongEnd() {
    if (!this.songs.length) return;
    if (this.repeatMode === 1) {
      this.audio.currentTime = 0;
      this.audio.play().catch((error) => {
        console.error("TuneTales: Error repeating song", error);
      });
    } else if (this.repeatMode === 2 || this.isShuffle) {
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

  /**
   * تنظیم ولوم صدا
   * @param {number} value - مقدار ولوم (0 تا 1)
   */
  setVolume(value) {
    const volume = parseFloat(value);
    if (isNaN(volume)) return;
    this.audio.volume = volume;
    if (this.$volumeDisplay?.length) {
      this.$volumeDisplay.text(Math.round(volume * 100));
    }
    if (this.$volumeSlider?.length) {
      this.$volumeSlider.css("--value", volume * 100 + "%");
    }
  }

  /**
   * جابجایی به زمان خاصی از آهنگ
   * @param {number} value - زمان به ثانیه
   */
  seek(value) {
    const time = parseFloat(value);
    if (isNaN(time)) return;
    this.isDragging = true;
    this.audio.currentTime = time;
    this.updateTime();
  }

  /**
   * پخش آهنگ از لیست پلی‌لیست
   * @param {number} index - شاخص آهنگ در پلی‌لیست
   */
  playFromList(index) {
    if (index >= 0 && index < this.songs.length) {
      this.playSong(index);
    }
  }

  /**
   * به‌روزرسانی رابط کاربری
   */
  updateUI() {
    if (!this.songs.length || !this.songs[this.currentSongIndex]) {
      console.warn("TuneTales: No valid song to update UI");
      return;
    }

    const song = this.songs[this.currentSongIndex];

    // به‌روزرسانی اطلاعات آهنگ
    if (this.$songTitle?.length)
      this.$songTitle.text(song.title || "Unknown Title");
    if (this.$songArtist?.length)
      this.$songArtist.text(song.artist || "Unknown Artist");
    if (this.$songAlbum?.length)
      this.$songAlbum.text(song.album || "Unknown Album");
    if (this.$songExcerpt?.length) this.$songExcerpt.text(song.excerpt || "");
    if (this.$songDescription?.length)
      this.$songDescription.text(song.description || "");

    // بارگذاری تصویر کاور
    if (this.$coverArt?.length && song.id) {
      this.loadCoverArt(song.id);
    } else {
      console.warn("TuneTales: No cover art element or invalid attachment ID");
    }

    // به‌روزرسانی دکمه پخش/توقف
    if (this.$playPauseBtn?.length) {
      const playIcon = '<i class="fas fa-play"></i>';
      const pauseIcon = '<i class="fas fa-pause"></i>';
      this.$playPauseBtn.html(this.isPlaying ? pauseIcon : playIcon);
      this.$playPauseBtn.attr("aria-label", this.isPlaying ? "Pause" : "Play");
    }

    // به‌روزرسانی دکمه حالت تصادفی
    if (this.$shuffleBtn?.length) {
      this.$shuffleBtn.toggleClass("active", this.isShuffle);
      this.$shuffleBtn.attr("aria-pressed", this.isShuffle);
    }

    // به‌روزرسانی دکمه تکرار
    if (this.$repeatBtn?.length) {
      let repeatIcon = '<i class="fas fa-redo"></i>';
      if (this.repeatMode === 1) {
        repeatIcon = '<i class="fas fa-redo"></i>';
        this.$repeatBtn
          .removeClass("repeat-one repeat-all")
          .addClass("active repeat-one");
        this.$repeatBtn.attr("title", "Repeat One");
      } else if (this.repeatMode === 2) {
        repeatIcon = '<i class="fas fa-redo"></i>';
        this.$repeatBtn
          .removeClass("repeat-one repeat-all")
          .addClass("active repeat-all");
        this.$repeatBtn.attr("title", "Repeat All");
      } else {
        this.$repeatBtn.removeClass("active repeat-one repeat-all");
        this.$repeatBtn.attr("title", "Repeat Off");
      }
      this.$repeatBtn.html(repeatIcon);
    }

    // به‌روزرسانی آیتم فعال در پلی‌لیست
    if (this.$playlistItems?.length) {
      this.$playlistItems.removeClass("active").attr("aria-selected", "false");
      this.$playlistItems
        .eq(this.currentSongIndex)
        .addClass("active")
        .attr("aria-selected", "true");
    }

    // به‌روزرسانی نوار پخش و زمان‌ها
    if (
      this.$seekbar?.length &&
      this.$durationTime?.length &&
      this.$currentTime?.length
    ) {
      if (this.audio.duration && !isNaN(this.audio.duration)) {
        this.$durationTime.text(this.formatTime(this.audio.duration));
        this.$seekbar.attr("max", this.audio.duration);
      } else {
        this.$durationTime.text("00:00");
        this.$seekbar.attr("max", 0);
      }
      this.$currentTime.text(this.formatTime(this.audio.currentTime));
      this.$seekbar.val(this.audio.currentTime);
      this.$seekbar.css(
        "--value",
        (this.audio.currentTime / (this.audio.duration || 1)) * 100 + "%"
      );
    }

    // به‌روزرسانی ولوم
    if (this.$volumeSlider?.length && this.$volumeDisplay?.length) {
      this.$volumeSlider.val(this.audio.volume);
      this.$volumeDisplay.text(Math.round(this.audio.volume * 100));
      this.$volumeSlider.css("--value", this.audio.volume * 100 + "%");
    }

    // به‌روزرسانی موقعیت آهنگ
    if (this.$songPosition?.length) {
      this.$songPosition.text(
        `${this.formatTime(this.audio.currentTime)} / ${this.formatTime(
          this.audio.duration || 0
        )}`
      );
    }
  }

  /**
   * بارگذاری تصویر کاور آهنگ از طریق AJAX
   * @param {number} attachmentId - شناسه پیوست رسانه
   */
  loadCoverArt(attachmentId) {
    if (!attachmentId || !this.$coverArt?.length) {
      console.warn("TuneTales: Invalid attachmentId or no coverArt element", {
        attachmentId,
      });
      return;
    }

    const defaultCover = tunetales_vars?.plugin_url
      ? `${tunetales_vars.plugin_url}assets/image/default-cover.jpg`
      : "https://via.placeholder.com/200";

    if (!tunetales_vars?.ajaxurl || !tunetales_vars?.nonce) {
      console.warn("TuneTales: AJAX URL or nonce missing", {
        ajaxurl: tunetales_vars?.ajaxurl,
        nonce: tunetales_vars?.nonce,
      });
      this.$coverArt.attr("src", defaultCover);
      return;
    }

    jQuery.ajax({
      url: tunetales_vars.ajaxurl,
      method: "POST",
      data: {
        action: "get_attachment_url",
        id: parseInt(attachmentId),
        size: "medium",
        nonce: tunetales_vars.nonce,
      },
      success: (response) => {
        if (response.success && response.data.url) {
          this.$coverArt.attr("src", response.data.url);
        } else {
          this.$coverArt.attr("src", defaultCover);
          console.warn(
            "TuneTales: Failed to load cover art, using default",
            response
          );
        }
      },
      error: (xhr, status, error) => {
        this.$coverArt.attr("src", defaultCover);
        console.error("TuneTales: AJAX error loading cover art", {
          status,
          error,
          xhr,
        });
      },
    });
  }

  /**
   * به‌روزرسانی متادیتا (مانند مدت زمان آهنگ)
   */
  updateMetadata() {
    if (
      this.$seekbar?.length &&
      this.audio.duration &&
      !isNaN(this.audio.duration)
    ) {
      this.$seekbar.attr("max", this.audio.duration);
    }
    if (this.$durationTime?.length) {
      this.$durationTime.text(this.formatTime(this.audio.duration || 0));
    }
  }

  /**
   * به‌روزرسانی زمان فعلی و نوار پخش
   */
  updateTime() {
    if (
      !this.isDragging &&
      this.$seekbar?.length &&
      this.$currentTime?.length
    ) {
      const progress =
        (this.audio.currentTime / (this.audio.duration || 1)) * 100 || 0;
      this.$seekbar.val(this.audio.currentTime);
      this.$seekbar.css("--value", `${progress}%`);
      this.$currentTime.text(this.formatTime(this.audio.currentTime));
    }
  }

  /**
   * تولید شاخص تصادفی برای حالت Shuffle
   * @returns {number} - شاخص تصادفی
   */
  getRandomIndex() {
    if (this.songs.length <= 1) return 0;
    let nextIndex;
    do {
      nextIndex = Math.floor(Math.random() * this.songs.length);
    } while (nextIndex === this.currentSongIndex);
    return nextIndex;
  }

  /**
   * فرمت کردن زمان به صورت mm:ss
   * @param {number} seconds - زمان به ثانیه
   * @returns {string} - زمان فرمت‌شده
   */
  formatTime(seconds) {
    if (isNaN(seconds)) return "00:00";
    const minutes = Math.floor(seconds / 60);
    seconds = Math.floor(seconds % 60);
    return `${minutes.toString().padStart(2, "0")}:${seconds
      .toString()
      .padStart(2, "0")}`;
  }

  /**
   * عقب‌گرد 15 ثانیه‌ای آهنگ
   */
  rewind15Seconds() {
    if (!this.songs.length || !this.audio.src) return;
    const newTime = Math.max(0, this.audio.currentTime - 15);
    this.audio.currentTime = newTime;
    this.updateUI();
  }

  /**
   * جلو بردن 15 ثانیه‌ای آهنگ
   */
  fastForward15Seconds() {
    if (!this.songs.length || !this.audio.src) return;
    const newTime = Math.min(
      this.audio.duration || 0,
      this.audio.currentTime + 15
    );
    this.audio.currentTime = newTime;
    this.updateUI();
  }
}

/**
 * مقداردهی اولیه پخش‌کننده هنگام بارگذاری صفحه
 */
jQuery(document).ready(function ($) {
  if (jQuery(".webapp-player").length) {
    new MusicPlayer();
    console.log("TuneTales: MusicPlayer initialized");
  } else {
    console.warn("TuneTales: Player container not found");
  }
});
