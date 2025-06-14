// تعریف کلاس MusicPlayer برای مدیریت پخش‌کننده موسیقی
class MusicPlayer {
  // سازنده کلاس
  constructor() {
    this.audio = new Audio(); // ایجاد نمونه از شیء Audio برای پخش فایل‌های صوتی
    this.songs = []; // آرایه‌ای برای ذخیره URL آهنگ‌ها
    this.currentSongIndex = 0; // شاخص آهنگ فعلی
    this.isPlaying = false; // وضعیت پخش (در حال پخش یا متوقف)
    this.isDragging = false; // وضعیت درگ کردن اسلایدر زمان
    this.isShuffle = false; // وضعیت حالت شافل
    this.repeatMode = 0; // حالت تکرار (0: خاموش، 1: تکرار تک، 2: تکرار همه)

    this.initElements(); // مقداردهی اولیه عناصر DOM
    this.loadSongs(); // بارگذاری لیست آهنگ‌ها
    this.bindEvents(); // اتصال رویدادها به عناصر
  }

  // متد برای مقداردهی اولیه عناصر DOM
  initElements() {
    this.$rewindBtn = jQuery(".rewind"); // دکمه عقب 15 ثانیه
    this.$fastForwardBtn = jQuery(".fast-forward"); // دکمه جلو 15 ثانیه
    this.$playPauseBtn = jQuery(".play-pause"); // دکمه پخش/توقف
    this.$nextBtn = jQuery(".next"); // دکمه آهنگ بعدی
    this.$prevBtn = jQuery(".prev"); // دکمه آهنگ قبلی
    this.$shuffleBtn = jQuery(".shuffle"); // دکمه شافل
    this.$repeatBtn = jQuery(".repeat"); // دکمه تکرار
    this.$backBtn = jQuery(".back-to-archive"); // دکمه بازگشت به آرشیو
    this.$volumeSlider = jQuery(".volume-slider"); // اسلایدر تنظیم صدا
    this.$seekbar = jQuery(".seekbar"); // اسلایدر پیشرفت آهنگ
    this.$playlistItems = jQuery(".playlist_item"); // آیتم‌های پلی‌لیست
    this.$coverArt = jQuery(".cover-art"); // تصویر کاور آهنگ
    this.$songTitle = jQuery(".song-info .song-title"); // عنوان آهنگ
    this.$songArtist = jQuery(".song-info .song-artist"); // نام هنرمند
    this.$songAlbum = jQuery(".song-info .song-album"); // نام آلبوم
    this.$songExcerpt = jQuery(".song-info .song-excerpt"); // خلاصه آهنگ
    this.$songDescription = jQuery(".song-info .song-description"); // توضیحات آهنگ
    this.$songPosition = jQuery(".song-info .song-position"); // موقعیت آهنگ در پلی‌لیست
    this.$sidebarToggle = jQuery(".sidebar-toggle"); // دکمه تغییر وضعیت سایدبار
    this.$sidebar = jQuery(".sidebar"); // سایدبار
  }

  // متد برای اتصال رویدادها به عناصر
  bindEvents() {
    // رویداد کلیک برای دکمه عقب 15 ثانیه
    this.$rewindBtn.on("click", () => this.rewind15Seconds());
    // رویداد کلیک برای دکمه جلو 15 ثانیه
    this.$fastForwardBtn.on("click", () => this.fastForward15Seconds());
    // رویداد کلیک برای دکمه پخش/توقف
    this.$playPauseBtn.on("click", () => this.togglePlayPause());
    // رویداد کلیک برای دکمه آهنگ بعدی
    this.$nextBtn.on("click", () => this.nextSong());
    // رویداد کلیک برای دکمه آهنگ قبلی
    this.$prevBtn.on("click", () => this.prevSong());
    // رویداد کلیک برای دکمه شافل
    this.$shuffleBtn.on("click", () => this.toggleShuffle());
    // رویداد کلیک برای دکمه تکرار
    this.$repeatBtn.on("click", () => this.toggleRepeat());
    // رویداد کلیک برای دکمه بازگشت به آرشیو
    this.$backBtn.on(
      "click",
      () => (window.location.href = tunetales_vars.archive_url) // هدایت به URL آرشیو
    );
    // رویداد ورودی برای اسلایدر صدا
    this.$volumeSlider.on("input", (e) => this.setVolume(e.target.value));
    // رویداد ورودی برای اسلایدر پیشرفت
    this.$seekbar.on("input", (e) => this.seek(e.target.value));
    // رویداد تغییر برای اسلایدر پیشرفت
    this.$seekbar.on("change", () => {
      this.isDragging = false; // پایان درگ کردن
    });
    // رویداد کلیک برای آیتم‌های پلی‌لیست
    this.$playlistItems.on("click", (e) => {
      if (!jQuery(e.target).hasClass("download-song")) {
        // پخش آهنگ از لیست، اگر روی لینک دانلود کلیک نشده باشد
        this.playFromList(jQuery(e.currentTarget).index());
      }
    });
    // رویداد کلیک برای دکمه تغییر وضعیت سایدبار
    this.$sidebarToggle.on("click", (e) => {
      e.stopPropagation(); // جلوگیری از انتشار رویداد
      this.toggleSidebar();
    });

    // رویداد کلیک برای بستن سایدبار در صورت کلیک خارج از آن
    jQuery(document).on("click", (e) => {
      if (
        this.$sidebar.hasClass("active") &&
        !this.$sidebar.is(e.target) &&
        this.$sidebar.has(e.target).length === 0 &&
        !this.$sidebarToggle.is(e.target)
      ) {
        this.$sidebar.removeClass("active"); // بستن سایدبار
      }
    });

    // رویداد keydown برای پشتیبانی از کنترل با کیبورد
    jQuery(document).on("keydown", (e) => {
      // جلوگیری از رفتار پیش‌فرض برای کلیدهای خاص
      if (
        ["Space", "ArrowRight", "ArrowLeft", "ArrowUp", "ArrowDown"].includes(
          e.code
        )
      ) {
        e.preventDefault();
      }

      // مدیریت کلیدهای کیبورد
      switch (e.code) {
        case "Space": // پخش/توقف
          this.togglePlayPause();
          this.$playPauseBtn.focus(); // فوکوس روی دکمه
          break;
        case "ArrowRight": // آهنگ بعدی
          this.nextSong();
          this.$nextBtn.focus();
          break;
        case "ArrowLeft": // آهنگ قبلی
          this.prevSong();
          this.$prevBtn.focus();
          break;
        case "KeyS": // شافل
          this.toggleShuffle();
          this.$shuffleBtn.focus();
          break;
        case "KeyR": // تکرار
          this.toggleRepeat();
          this.$repeatBtn.focus();
          break;
        case "ArrowUp": // جلو 15 ثانیه
          this.fastForward15Seconds();
          this.$fastForwardBtn.focus();
          break;
        case "ArrowDown": // عقب 15 ثانیه
          this.rewind15Seconds();
          this.$rewindBtn.focus();
          break;
        case "KeyF": // جلو 15 ثانیه
          this.fastForward15Seconds();
          this.$fastForwardBtn.focus();
          break;
        case "KeyB": // عقب 15 ثانیه
          this.rewind15Seconds();
          this.$rewindBtn.focus();
          break;
      }
    });

    // رویداد keydown برای انتخاب آهنگ از لیست با Enter
    this.$playlistItems.on("keydown", (e) => {
      if (e.code === "Enter") {
        this.playFromList(jQuery(e.currentTarget).index()); // پخش آهنگ انتخاب‌شده
      }
    });

    // رویدادهای صوتی
    this.audio.addEventListener("loadedmetadata", () => this.updateMetadata()); // به‌روزرسانی متادیتا
    this.audio.addEventListener("timeupdate", () => this.updateTime()); // به‌روزرسانی زمان
    this.audio.addEventListener("ended", () => this.handleSongEnd()); // مدیریت پایان آهنگ
    this.audio.addEventListener("progress", () => this.updateBuffering()); // به‌روزرسانی بافر
  }

  // متد برای تغییر وضعیت سایدبار
  toggleSidebar() {
    this.$sidebar.toggleClass("active"); // افزودن یا حذف کلاس active
  }

  // متد برای بارگذاری لیست آهنگ‌ها
  loadSongs() {
    this.$playlistItems.each((_, item) => {
      const src = jQuery(item).data("src"); // دریافت URL آهنگ
      if (src) this.songs.push(src); // افزودن به آرایه آهنگ‌ها
    });
    if (this.songs.length > 0) this.updateUI(); // به‌روزرسانی رابط کاربری
  }

  // متد برای پخش آهنگ با شاخص مشخص
  playSong(index) {
    if (index < 0 || index >= this.songs.length) return; // بررسی معتبر بودن شاخص
    this.currentSongIndex = index; // تنظیم شاخص آهنگ فعلی
    this.audio.src = this.songs[index]; // تنظیم منبع صوتی
    this.audio.load(); // بارگذاری فایل صوتی
    this.audio
      .play()
      .catch((error) => console.error("Error playing audio:", error)); // پخش با مدیریت خطا
    this.isPlaying = true; // تنظیم وضعیت پخش
    this.updateUI(); // به‌روزرسانی رابط کاربری
  }

  // متد برای توقف آهنگ
  pauseSong() {
    this.audio.pause(); // توقف پخش
    this.isPlaying = false; // تنظیم وضعیت توقف
    this.updateUI(); // به‌روزرسانی رابط کاربری
  }

  // متد برای تغییر وضعیت پخش/توقف
  togglePlayPause() {
    if (this.isPlaying) {
      this.pauseSong(); // توقف در صورت پخش
    } else {
      if (!this.audio.src) {
        this.playSong(this.currentSongIndex); // پخش آهنگ اول اگر منبعی وجود ندارد
      } else {
        this.audio
          .play()
          .catch((error) => console.error("Error resuming audio:", error)); // ادامه پخش
        this.isPlaying = true;
        this.updateUI();
      }
    }
  }

  // متد برای پخش آهنگ بعدی
  nextSong() {
    if (this.isShuffle) {
      this.currentSongIndex = this.getRandomIndex(); // انتخاب تصادفی در حالت شافل
    } else {
      this.currentSongIndex = (this.currentSongIndex + 1) % this.songs.length; // آهنگ بعدی
    }
    this.playSong(this.currentSongIndex); // پخش آهنگ جدید
  }

  // متد برای پخش آهنگ قبلی
  prevSong() {
    if (this.isShuffle) {
      this.currentSongIndex = this.getRandomIndex(); // انتخاب تصادفی در حالت شافل
    } else {
      this.currentSongIndex =
        (this.currentSongIndex - 1 + this.songs.length) % this.songs.length; // آهنگ قبلی
    }
    this.playSong(this.currentSongIndex); // پخش آهنگ جدید
  }

  // متد برای تغییر وضعیت شافل
  toggleShuffle() {
    if (this.songs.length <= 1) {
      alert("Shuffle mode requires more than one song."); // هشدار در صورت کمبود آهنگ
      return;
    }
    this.isShuffle = !this.isShuffle; // تغییر وضعیت شافل
    this.$shuffleBtn.toggleClass("active"); // به‌روزرسانی ظاهر دکمه
    if (this.isShuffle && this.isPlaying) this.nextSong(); // پخش آهنگ بعدی در حالت شافل
  }

  // متد برای تغییر حالت تکرار
  toggleRepeat() {
    this.repeatMode = (this.repeatMode + 1) % 3; // چرخش بین حالت‌ها (0, 1, 2)
    this.$repeatBtn.removeClass("active repeat-one repeat-all"); // حذف کلاس‌های قبلی
    if (this.repeatMode === 1) {
      this.$repeatBtn.addClass("active repeat-one"); // حالت تکرار تک
      this.$repeatBtn.attr("title", "Repeat One");
    } else if (this.repeatMode === 2) {
      this.$repeatBtn.addClass("active repeat-all"); // حالت تکرار همه
      this.$repeatBtn.attr("title", "Repeat All");
    } else {
      this.$repeatBtn.attr("title", "Repeat Off"); // حالت خاموش
    }
  }

  // متد برای مدیریت پایان آهنگ
  handleSongEnd() {
    if (this.repeatMode === 1) {
      this.audio.currentTime = 0; // بازگشت به ابتدا
      this.audio
        .play()
        .catch((error) => console.error("Error repeating song:", error)); // پخش مجدد
    } else if (this.repeatMode === 2) {
      this.nextSong(); // پخش آهنگ بعدی
    } else if (this.isShuffle) {
      this.nextSong(); // پخش آهنگ تصادفی
    } else {
      if (this.currentSongIndex + 1 < this.songs.length) {
        this.nextSong(); // پخش آهنگ بعدی
      } else {
        this.pauseSong(); // توقف پخش
        this.currentSongIndex = 0; // بازگشت به آهنگ اول
        this.updateUI(); // به‌روزرسانی رابط کاربری
      }
    }
  }

  // متد برای تنظیم صدا
  setVolume(value) {
    this.audio.volume = parseFloat(value); // تنظیم ولوم
    jQuery(".volume-display").text(Math.round(value * 100)); // نمایش درصد صدا
  }

  // متد برای جابجایی در آهنگ
  seek(value) {
    this.isDragging = true; // فعال کردن حالت درگ
    this.audio.currentTime = value; // تنظیم زمان فعلی
    jQuery(".current-time").text(this.formatTime(value)); // به‌روزرسانی زمان
  }

  // متد برای پخش آهنگ از لیست
  playFromList(index) {
    this.playSong(index); // پخش آهنگ با شاخص مشخص
  }

  // متد برای به‌روزرسانی رابط کاربری
  updateUI() {
    // تغییر آیکون دکمه پخش/توقف
    const iconClass = this.isPlaying ? "fa-pause" : "fa-play";
    this.$playPauseBtn
      .find("i")
      .removeClass("fa-play fa-pause")
      .addClass(iconClass);
    // به‌روزرسانی وضعیت آیتم‌های پلی‌لیست
    this.$playlistItems
      .removeClass("playing")
      .eq(this.currentSongIndex)
      .addClass("playing");

    // دریافت اطلاعات آهنگ از آیتم پلی‌لیست
    const songData = this.$playlistItems.eq(this.currentSongIndex);
    const title = songData.data("title") || "Unknown Title"; // عنوان آهنگ
    const artist = songData.data("artist") || "Unknown Artist"; // نام هنرمند
    const album = songData.data("album") || "Unknown Album"; // نام آلبوم
    const excerpt = songData.data("excerpt") || ""; // خلاصه
    const description = songData.data("description") || ""; // توضیحات
    this.$songTitle.text(title); // به‌روزرسانی عنوان
    this.$songArtist.text(artist); // به‌روزرسانی هنرمند
    this.$songAlbum.text(album); // به‌روزرسانی آلبوم
    this.$songExcerpt.text(excerpt); // به‌روزرسانی خلاصه
    this.$songDescription.text(description); // به‌روزرسانی توضیحات
    // به‌روزرسانی موقعیت آهنگ
    this.$songPosition.text(
      `Song ${this.currentSongIndex + 1} of ${this.songs.length}`
    );

    const src = songData.data("src"); // منبع آهنگ
    const defaultCover =
      tunetales_vars.plugin_url + "../assets/image/default-cover.jpg"; // تصویر پیش‌فرض
    if (!src) {
      console.error("Song source is empty"); // خطا در صورت خالی بودن منبع
      this.$coverArt.removeClass("fade"); // حذف انیمیشن
      this.$coverArt.attr("src", defaultCover); // تنظیم تصویر پیش‌فرض
      this.$coverArt.addClass("fade"); // افزودن انیمیشن
      return;
    }

    console.log("Sending AJAX request with src:", src); // لاگ درخواست AJAX

    // درخواست AJAX برای دریافت ID پیوست
    jQuery.ajax({
      url: tunetales_vars.ajaxurl, // URL درخواست
      method: "POST",
      data: {
        action: "get_attachment_id", // اکشن وردپرس
        url: src, // URL آهنگ
        nonce: tunetales_vars.nonce, // نانس امنیتی
      },
      success: (response) => {
        console.log("AJAX response for get_attachment_id:", response); // لاگ پاسخ
        if (response.success) {
          const attachmentId = response.data.id || 0; // ID پیوست
          if (attachmentId) {
            // درخواست AJAX برای دریافت URL تصویر پیوست
            jQuery.ajax({
              url: tunetales_vars.ajaxurl,
              method: "POST",
              data: {
                action: "get_attachment_url", // اکشن وردپرس
                id: attachmentId, // ID پیوست
                size: "medium", // اندازه تصویر
                nonce: tunetales_vars.nonce,
              },
              success: (response) => {
                console.log("AJAX response for get_attachment_url:", response); // لاگ پاسخ
                if (response.success) {
                  this.$coverArt.removeClass("fade"); // حذف انیمیشن
                  this.$coverArt.attr("src", response.data.url || defaultCover); // تنظیم تصویر
                  this.$coverArt.addClass("fade"); // افزودن انیمیشن
                } else {
                  console.error(
                    "Error fetching attachment URL:",
                    response.data.message
                  ); // لاگ خطا
                  this.$coverArt.removeClass("fade");
                  this.$coverArt.attr("src", defaultCover);
                  this.$coverArt.addClass("fade");
                }
              },
              error: (xhr, status, error) => {
                console.error("Error fetching attachment URL:", status, error); // لاگ خطا
                this.$coverArt.removeClass("fade");
                this.$coverArt.attr("src", defaultCover);
                this.$coverArt.addClass("fade");
              },
            });
          } else {
            this.$coverArt.removeClass("fade"); // حذف انیمیشن
            this.$coverArt.attr("src", defaultCover); // تصویر پیش‌فرض
            this.$coverArt.addClass("fade"); // افزودن انیمیشن
          }
        } else {
          console.error("Error fetching attachment ID:", response.data.message); // لاگ خطا
          this.$coverArt.removeClass("fade");
          this.$coverArt.attr("src", defaultCover);
          this.$coverArt.addClass("fade");
        }
      },
      error: (xhr, status, error) => {
        console.error("Error fetching attachment ID:", status, error); // لاگ خطا
        this.$coverArt.removeClass("fade");
        this.$coverArt.attr("src", defaultCover);
        this.$coverArt.addClass("fade");
      },
    });
  }

  // متد برای به‌روزرسانی متادیتا
  updateMetadata() {
    this.$seekbar.attr("max", this.audio.duration); // تنظیم حداکثر اسلایدر
    jQuery(".duration-time").text(this.formatTime(this.audio.duration)); // نمایش مدت زمان
  }

  // متد برای به‌روزرسانی زمان پخش
  updateTime() {
    if (!this.isDragging) {
      const progress =
        (this.audio.currentTime / this.audio.duration) * 100 || 0; // محاسبه پیشرفت
      this.$seekbar.val(this.audio.currentTime); // تنظیم مقدار اسلایدر
      this.$seekbar.css("--value", `${progress}%`); // به‌روزرسانی استایل
      jQuery(".current-time").text(this.formatTime(this.audio.currentTime)); // نمایش زمان فعلی
    }
  }

  // متد برای به‌روزرسانی وضعیت بافر
  updateBuffering() {
    if (this.audio.buffered.length > 0) {
      const bufferedEnd = this.audio.buffered.end(
        this.audio.buffered.length - 1
      ); // پایان بافر
      const percentage = (bufferedEnd / this.audio.duration) * 100; // درصد بافر
      jQuery(".buffering-bar").css("width", `${percentage}%`); // به‌روزرسانی نوار بافر
    }
  }

  // متد برای دریافت شاخص تصادفی
  getRandomIndex() {
    let nextIndex;
    do {
      nextIndex = Math.floor(Math.random() * this.songs.length); // شاخص تصادفی
    } while (nextIndex === this.currentSongIndex); // جلوگیری از تکرار آهنگ فعلی
    return nextIndex;
  }

  // متد برای فرمت زمان
  formatTime(seconds) {
    const minutes = Math.floor(seconds / 60); // محاسبه دقیقه
    const secs = Math.floor(seconds % 60); // محاسبه ثانیه
    return `${minutes < 10 ? "0" : ""}${minutes}:${
      secs < 10 ? "0" : ""
    }${secs}`; // فرمت MM:SS
  }

  // متد برای عقب بردن 15 ثانیه
  rewind15Seconds() {
    const newTime = Math.max(0, this.audio.currentTime - 15); // کاهش 15 ثانیه
    this.audio.currentTime = newTime; // تنظیم زمان
    this.updateTime(); // به‌روزرسانی زمان
  }

  // متد برای جلو بردن 15 ثانیه
  fastForward15Seconds() {
    const newTime = Math.min(this.audio.duration, this.audio.currentTime + 15); // افزایش 15 ثانیه
    this.audio.currentTime = newTime; // تنظیم زمان
    this.updateTime(); // به‌روزرسانی زمان
  }
}

// ایجاد نمونه از MusicPlayer هنگام آماده شدن DOM
jQuery(document).ready(() => new MusicPlayer());
