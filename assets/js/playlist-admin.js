// اطمینان از آماده بودن DOM قبل از اجرای کد jQuery
jQuery(document).ready(function ($) {
  // انتخاب wrapper برای آیتم‌های پلی‌لیست
  const $wrapper = $("#playlist_songs_wrapper");
  // انتخاب دکمه افزودن آهنگ
  const $addButton = $("#add_song_button");

  // رویداد کلیک برای دکمه افزودن آهنگ
  $addButton.on("click", function () {
    // ایجاد نمونه از رسانه‌گزین وردپرس برای انتخاب فایل صوتی
    const uploader = wp.media({
      title: "Add Song to Playlist", // عنوان پنجره رسانه
      library: { type: "audio" }, // محدود کردن به فایل‌های صوتی
      button: { text: "Add to Playlist" }, // متن دکمه انتخاب
      multiple: false, // غیرفعال کردن انتخاب چندگانه
    });

    // رویداد انتخاب فایل در رسانه‌گزین
    uploader.on("select", function () {
      const selection = uploader.state().get("selection"); // دریافت فایل‌های انتخاب‌شده
      // پیمایش فایل‌های انتخاب‌شده
      selection.each(function (attachment) {
        const songUrl = attachment.attributes.url; // URL فایل صوتی
        const songId = attachment.id; // ID فایل صوتی
        // ایجاد HTML برای آیتم آهنگ جدید
        const $songItem = $(`
                    <div class="playlist_song_item">
                        <input type="text" name="playlist_songs[]" value="${songUrl}" class="playlist_song_input" readonly />
                        <button type="button" class="button remove_song_button">Remove</button>
                    </div>
                `);
        // افزودن آیتم آهنگ به wrapper
        $wrapper.append($songItem);

        // ارسال درخواست AJAX برای ذخیره فایل در مسیر سفارشی
        $.ajax({
          url: playlist_admin_ajax.ajax_url, // URL درخواست AJAX
          method: "POST", // متد درخواست
          data: {
            action: "save_song_to_custom_directory", // اکشن وردپرس
            song_id: songId, // ID فایل صوتی
            post_id: $("#post_ID").val(), // ID پست فعلی
            _ajax_nonce: playlist_admin_ajax.nonce, // نانس امنیتی
          },
          // مدیریت پاسخ موفق
          success: function (response) {
            if (response.success) {
              // به‌روزرسانی URL ورودی با URL جدید از پاسخ
              $songItem
                .find(".playlist_song_input")
                .val(response.data.new_song_url);
            } else {
              // نمایش خطا در کنسول در صورت عدم موفقیت
              console.error("Error saving song:", response.data.message);
            }
          },
          // مدیریت خطای AJAX
          error: function (xhr, status, error) {
            console.error("AJAX error:", status, error);
          },
        });
      });
    });

    // باز کردن پنجره رسانه‌گزین
    uploader.open();
  });

  // رویداد کلیک برای دکمه افزودن چندین آهنگ
  $("#add_multiple_songs_button").on("click", function (e) {
    e.preventDefault(); // جلوگیری از رفتار پیش‌فرض دکمه
    // ایجاد نمونه از رسانه‌گزین وردپرس برای انتخاب چندگانه فایل‌های صوتی
    var frame = wp.media({
      title: "Select Songs", // عنوان پنجره رسانه
      button: { text: "Add to Playlist" }, // متن دکمه انتخاب
      multiple: true, // فعال کردن انتخاب چندگانه
      library: { type: "audio" }, // محدود کردن به فایل‌های صوتی
    });

    // رویداد انتخاب فایل‌ها در رسانه‌گزین
    frame.on("select", function () {
      var attachments = frame.state().get("selection").toJSON(); // دریافت فایل‌های انتخاب‌شده به‌صورت JSON
      var wrapper = $("#playlist_songs_wrapper"); // انتخاب wrapper پلی‌لیست
      var playlists = playlist_admin_ajax.playlists || []; // دریافت لیست پلی‌لیست‌ها
      var currentPlaylistId = playlist_admin_ajax.current_playlist_id; // ID پلی‌لیست فعلی
      // پیمایش فایل‌های انتخاب‌شده
      attachments.forEach(function (attachment, index) {
        var url = attachment.url; // URL فایل صوتی
        var checkboxOptions = ""; // رشته برای گزینه‌های چک‌باکس
        // ایجاد گزینه‌های چک‌باکس برای هر پلی‌لیست
        playlists.forEach(function (playlist) {
          var checked = playlist.id == currentPlaylistId ? "checked" : ""; // بررسی انتخاب پیش‌فرض
          checkboxOptions +=
            '<label class="checkbox-item">' +
            '<input type="checkbox" name="playlist_songs[playlists][' +
            index +
            '][]" ' +
            'value="' +
            playlist.id +
            '" ' +
            checked +
            " />" +
            playlist.title +
            "</label>";
        });
        // افزودن HTML آیتم آهنگ جدید به wrapper
        wrapper.append(
          '<div class="playlist_song_item">' +
            '<div class="song-url-wrapper">' +
            '<input type="text" name="playlist_songs[url][]" value="' +
            url +
            '" class="playlist_song_input" readonly />' +
            "</div>" +
            '<div class="playlist-actions">' +
            '<div class="playlist-checkboxes">' +
            "<p>Select Playlists:</p>" +
            '<div class="checkbox-list">' +
            checkboxOptions +
            "</div>" +
            "</div>" +
            '<div class="new-playlist-wrapper">' +
            '<input type="text" class="new_playlist_input" placeholder="New Playlist" />' +
            '<button type="button" class="button add_new_playlist_button">' +
            '<span class="dashicons dashicons-plus-alt"></span> Add' +
            "</button>" +
            "</div>" +
            '<button type="button" class="button remove_song_button">' +
            '<span class="dashicons dashicons-trash"></span> Remove' +
            "</button>" +
            "</div>" +
            "</div>"
        );
      });
    });

    // باز کردن پنجره رسانه‌گزین
    frame.open();
  });

  // رویداد کلیک برای حذف آیتم آهنگ
  $(document).on("click", ".remove_song_button", function () {
    // حذف آیتم آهنگ والد دکمه کلیک‌شده
    $(this).closest(".playlist_song_item").remove();
  });

  // رویداد کلیک برای دکمه افزودن پلی‌لیست جدید
  $(document).on("click", ".add_new_playlist_button", function () {
    var button = $(this); // دکمه کلیک‌شده
    var input = button.siblings(".new_playlist_input"); // ورودی نام پلی‌لیست
    var playlistName = input.val().trim(); // دریافت نام پلی‌لیست و حذف فاصله‌های اضافی
    var checkboxList = button
      .closest(".playlist-actions")
      .find(".checkbox-list"); // یافتن لیست چک‌باکس‌ها

    // بررسی خالی نبودن نام پلی‌لیست
    if (!playlistName) {
      alert("Please enter a playlist name");
      return;
    }

    // ارسال درخواست AJAX برای ایجاد پلی‌لیست جدید
    $.ajax({
      url: playlist_admin_ajax.ajax_url, // URL درخواست AJAX
      type: "POST", // متد درخواست
      data: {
        action: "create_new_playlist", // اکشن وردپرس
        nonce: playlist_admin_ajax.nonce, // نانس امنیتی
        playlist_name: playlistName, // نام پلی‌لیست
      },
      // مدیریت پاسخ موفق
      success: function (response) {
        if (response.success) {
          var index = button.closest(".playlist_song_item").index(); // شاخص آیتم آهنگ
          // ایجاد چک‌باکس جدید برای پلی‌لیست ایجادشده
          var newCheckbox =
            '<label class="checkbox-item">' +
            '<input type="checkbox" name="playlist_songs[playlists][' +
            index +
            '][]" ' +
            'value="' +
            response.data.id +
            '" checked />' +
            response.data.title +
            "</label>";
          // افزودن چک‌باکس به لیست
          checkboxList.append(newCheckbox);
          // پاک کردن ورودی
          input.val("");
          // افزودن پلی‌لیست جدید به لیست پلی‌لیست‌ها
          playlist_admin_ajax.playlists.push({
            id: response.data.id,
            title: response.data.title,
          });
        } else {
          // نمایش خطا در صورت عدم موفقیت
          alert("Error: " + response.data.message);
        }
      },
      // مدیریت خطای AJAX
      error: function () {
        alert("Error creating playlist");
      },
    });
  });
});
