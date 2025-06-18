jQuery(document).ready(function ($) {
  // انتخاب wrapper برای آیتم‌های پلی‌لیست
  const $wrapper = $("#playlist_songs_wrapper");

  // رویداد کلیک برای دکمه افزودن یک آهنگ
  $("#add_song_button").on("click", function () {
    const uploader = wp.media({
      title: "Add Song to Playlist",
      library: { type: "audio" },
      button: { text: "Add to Playlist" },
      multiple: false,
    });

    uploader.on("select", function () {
      const attachment = uploader.state().get("selection").first().toJSON();
      console.log("TuneTales: Adding single song", attachment);
      appendSongItem(attachment, $wrapper);
      updatePlaylistCheckboxes();
    });

    uploader.open();
  });

  // رویداد کلیک برای دکمه افزودن چندین آهنگ
  $("#add_multiple_songs_button").on("click", function (e) {
    e.preventDefault();
    const frame = wp.media({
      title: "Select Songs",
      button: { text: "Add to Playlist" },
      multiple: true,
      library: { type: "audio" },
    });

    frame.on("select", function () {
      const attachments = frame.state().get("selection").toJSON();
      console.log("TuneTales: Adding multiple songs", attachments);
      attachments.forEach(function (attachment) {
        appendSongItem(attachment, $wrapper);
      });
      updatePlaylistCheckboxes();
    });

    frame.open();
  });

  // رویداد کلیک برای حذف آیتم آهنگ
  $(document).on("click", ".remove_song_button", function () {
    $(this).closest(".playlist_song_item").remove();
    console.log("TuneTales: Removed song item from metabox");
    updatePlaylistCheckboxes();
  });

  // رویداد کلیک برای افزودن پلی‌لیست جدید
  $(document).on("click", ".add_new_playlist_button", function () {
    const $button = $(this);
    const $input = $button.siblings(".new_playlist_input");
    const playlistName = $input.val().trim();

    if (!playlistName) {
      alert("Please enter a playlist name.");
      return;
    }

    $.ajax({
      url: playlist_admin_ajax.ajax_url,
      method: "POST",
      data: {
        action: "create_new_playlist",
        playlist_name: playlistName,
        nonce: playlist_admin_ajax.nonce,
      },
      success: function (response) {
        if (response.success) {
          console.log("TuneTales: Created new playlist", response.data);
          playlist_admin_ajax.playlists.push({
            id: response.data.id,
            title: response.data.title,
          });
          $input.val("");
          updatePlaylistCheckboxes();
        } else {
          alert("Error: " + response.data.message);
        }
      },
      error: function (xhr, status, error) {
        console.error("TuneTales: AJAX error creating playlist", status, error);
        alert("Error creating playlist.");
      },
    });
  });

  // رویداد کلیک برای ذخیره متادیتا
  $(document).on("click", ".save-song-metadata", function () {
    const $button = $(this);
    const songId = $button.data("song-id");
    const $artistInput = $button.siblings(".song-artist-input");
    const $albumInput = $button.siblings(".song-album-input");
    const artist = $artistInput.val().trim();
    const album = $albumInput.val().trim();

    $.ajax({
      url: playlist_admin_ajax.ajax_url,
      method: "POST",
      data: {
        action: "save_song_metadata",
        song_id: songId,
        artist: artist,
        album: album,
        nonce: playlist_admin_ajax.nonce,
      },
      success: function (response) {
        console.log("TuneTales: Saved metadata for song", songId, response);
        alert(response.data.message);
        $artistInput.val(artist);
        $albumInput.val(album);
      },
      error: function (xhr, status, error) {
        console.error("TuneTales: AJAX error saving metadata", status, error);
        alert("Error saving metadata.");
      },
    });
  });

  // تابع برای افزودن آیتم آهنگ به متاباکس
  function appendSongItem(attachment, $wrapper) {
    const index = $(".playlist_song_item").length;
    const songUrl = attachment.url;
    const songId = attachment.id;
    const currentPlaylistId = playlist_admin_ajax.current_playlist_id;
    let checkboxOptions = "";

    (playlist_admin_ajax.playlists || []).forEach(function (playlist) {
      const checked = playlist.id == currentPlaylistId ? "checked" : "";
      checkboxOptions += `
                <label class="checkbox-item">
                    <input type="checkbox" name="playlist_songs[playlists][${index}][]" value="${playlist.id}" ${checked} />
                    ${playlist.title}
                </label>
            `;
    });

    const $songItem = $(`
            <div class="playlist_song_item">
                <div class="song-url-wrapper">
                    <input type="hidden" name="playlist_songs[attachment_id][]" value="${songId}" />
                    <input type="text" value="${songUrl}" class="playlist_song_input" readonly />
                </div>
                <div class="playlist-actions">
                    <div class="playlist-checkboxes">
                        <p>Select Playlists:</p>
                        <div class="checkbox-list">
                            ${checkboxOptions}
                        </div>
                    </div>
                    <div class="metadata-wrapper">
                        <input type="text" class="song-artist-input" placeholder="Artist" value="${
                          attachment.meta?.artist || ""
                        }" />
                        <input type="text" class="song-album-input" placeholder="Album" value="${
                          attachment.meta?.album || ""
                        }" />
                        <button type="button" class="button save-song-metadata" data-song-id="${songId}">Save Metadata</button>
                    </div>
                    <div class="new-playlist-wrapper">
                        <input type="text" class="new_playlist_input" placeholder="New Playlist" />
                        <button type="button" class="button add_new_playlist_button">
                            <span class="dashicons dashicons-plus-alt"></span> Add
                        </button>
                    </div>
                    <button type="button" class="button remove_song_button">
                        <span class="dashicons dashicons-trash"></span> Remove
                    </button>
                </div>
            </div>
        `);

    $wrapper.append($songItem);
    console.log("TuneTales: Appended song item", { id: songId, url: songUrl });
  }

  // تابع برای به‌روزرسانی چک‌باکس‌های پلی‌لیست
  function updatePlaylistCheckboxes() {
    $(".playlist_song_item").each(function (index) {
      const $item = $(this);
      const $checkboxList = $item.find(".checkbox-list");
      const currentChecks = $checkboxList
        .find("input:checked")
        .map(function () {
          return $(this).val();
        })
        .get();
      $checkboxList.empty();

      (playlist_admin_ajax.playlists || []).forEach(function (playlist) {
        const checked = currentChecks.includes(playlist.id.toString())
          ? "checked"
          : "";
        $checkboxList.append(`
                    <label class="checkbox-item">
                        <input type="checkbox" name="playlist_songs[playlists][${index}][]" value="${playlist.id}" ${checked} />
                        ${playlist.title}
                    </label>
                `);
      });
    });
    console.log("TuneTales: Updated playlist checkboxes");
  }
});
