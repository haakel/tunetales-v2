jQuery(document).ready(function ($) {
  const $wrapper = $("#playlist_songs_wrapper");
  const $addButton = $("#add_song_button");

  $addButton.on("click", function () {
    const uploader = wp.media({
      title: "Add Song to Playlist",
      library: { type: "audio" },
      button: { text: "Add to Playlist" },
      multiple: false,
    });

    uploader.on("select", function () {
      const selection = uploader.state().get("selection");
      selection.each(function (attachment) {
        const songUrl = attachment.attributes.url;
        const songId = attachment.id;
        const $songItem = $(`
                    <div class="playlist_song_item">
                        <input type="text" name="playlist_songs[]" value="${songUrl}" class="playlist_song_input" readonly />
                        <button type="button" class="button remove_song_button">Remove</button>
                    </div>
                `);
        $wrapper.append($songItem);

        $.ajax({
          url: playlist_admin_ajax.ajax_url,
          method: "POST",
          data: {
            action: "save_song_to_custom_directory",
            song_id: songId,
            post_id: $("#post_ID").val(),
            _ajax_nonce: playlist_admin_ajax.nonce,
          },
          success: function (response) {
            if (response.success) {
              $songItem
                .find(".playlist_song_input")
                .val(response.data.new_song_url);
            } else {
              console.error("Error saving song:", response.data.message);
            }
          },
          error: function (xhr, status, error) {
            console.error("AJAX error:", status, error);
          },
        });
      });
    });

    uploader.open();
  });
  $("#add_multiple_songs_button").on("click", function (e) {
    e.preventDefault();
    var frame = wp.media({
      title: "Select Songs",
      button: { text: "Add to Playlist" },
      multiple: true,
      library: { type: "audio" },
    });

    frame.on("select", function () {
      var attachments = frame.state().get("selection").toJSON();
      var wrapper = $("#playlist_songs_wrapper");
      var playlists = playlist_admin_ajax.playlists || [];
      var currentPlaylistId = playlist_admin_ajax.current_playlist_id;
      attachments.forEach(function (attachment, index) {
        var url = attachment.url;
        var checkboxOptions = "";
        playlists.forEach(function (playlist) {
          var checked = playlist.id == currentPlaylistId ? "checked" : "";
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

    frame.open();
  });

  $(document).on("click", ".remove_song_button", function () {
    $(this).closest(".playlist_song_item").remove();
  });

  $(document).on("click", ".add_new_playlist_button", function () {
    var button = $(this);
    var input = button.siblings(".new_playlist_input");
    var playlistName = input.val().trim();
    var checkboxList = button
      .closest(".playlist-actions")
      .find(".checkbox-list");

    if (!playlistName) {
      alert("Please enter a playlist name");
      return;
    }

    $.ajax({
      url: playlist_admin_ajax.ajax_url,
      type: "POST",
      data: {
        action: "create_new_playlist",
        nonce: playlist_admin_ajax.nonce,
        playlist_name: playlistName,
      },
      success: function (response) {
        if (response.success) {
          var index = button.closest(".playlist_song_item").index();
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
          checkboxList.append(newCheckbox);
          input.val("");
          playlist_admin_ajax.playlists.push({
            id: response.data.id,
            title: response.data.title,
          });
        } else {
          alert("Error: " + response.data.message);
        }
      },
      error: function () {
        alert("Error creating playlist");
      },
    });
  });
});
