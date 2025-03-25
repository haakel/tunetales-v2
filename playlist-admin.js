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

  $wrapper.on("click", ".remove_song_button", function () {
    $(this).closest(".playlist_song_item").remove();
  });
});
