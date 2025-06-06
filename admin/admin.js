jQuery(document).ready(function ($) {
  "use strict";

  // Media uploader variables
  let audioFrame, coverFrame;

  // Initialize sortable for tracks
  $("#music-playlist-tracks-list").sortable({
    handle: ".column-order",
    axis: "y",
    helper: function (e, ui) {
      ui.children().each(function () {
        $(this).width($(this).width());
      });
      return ui;
    },
    update: function () {
      const tracksOrder = [];

      $("#music-playlist-tracks-list tr").each(function () {
        tracksOrder.push($(this).data("id"));
      });

      // Send AJAX request to update order
      $.ajax({
        url: musicPlaylist.ajaxUrl,
        type: "POST",
        data: {
          action: "music_playlist_update_track_order",
          nonce: musicPlaylist.nonce,
          tracks_order: tracksOrder,
        },
        success: function (response) {
          if (response.success) {
            // Optional: show success message
            console.log(response.data.message);
          } else {
            console.error("Failed to update track order");
          }
        },
        error: function () {
          console.error("AJAX error");
        },
      });
    },
  });

  // Select audio file button
  $("#select-audio-file").on("click", function (e) {
    e.preventDefault();

    // If the frame already exists, reopen it
    if (audioFrame) {
      audioFrame.open();
      return;
    }

    // Create a new media frame
    audioFrame = wp.media({
      title: musicPlaylist.i18n.selectAudio,
      button: {
        text: musicPlaylist.i18n.addTrack,
      },
      library: {
        type: "audio",
      },
      multiple: false,
    });

    // When a file is selected, run a callback
    audioFrame.on("select", function () {
      // Get the attachment from the modal frame
      const attachment = audioFrame.state().get("selection").first().toJSON();

      // Set the field value
      $("#track-file").val(attachment.url);

      // Try to get duration if available
      if (attachment.fileLength) {
        // Convert duration to seconds
        const duration = Math.round(attachment.fileLength);
        $("#track-duration").val(duration);
      }
    });

    // Open the modal
    audioFrame.open();
  });

  // Select cover image button
  $("#select-cover-image").on("click", function (e) {
    e.preventDefault();

    // If the frame already exists, reopen it
    if (coverFrame) {
      coverFrame.open();
      return;
    }

    // Create a new media frame
    coverFrame = wp.media({
      title: musicPlaylist.i18n.selectCover,
      button: {
        text: "Set Cover Image",
      },
      library: {
        type: "image",
      },
      multiple: false,
    });

    // When an image is selected, run a callback
    coverFrame.on("select", function () {
      // Get the attachment from the modal frame
      const attachment = coverFrame.state().get("selection").first().toJSON();

      // Set the field value
      $("#track-cover").val(attachment.url);

      // Show preview
      $("#cover-preview").html(
        '<img src="' + attachment.url + '" alt="Cover Preview">'
      );
    });

    // Open the modal
    coverFrame.open();
  });

  // Form submission for adding track
  $("#music-playlist-add-track-form").on("submit", function (e) {
    e.preventDefault();

    const $form = $(this);
    const $submitButton = $("#add-track-btn");

    // Disable submit button and show loading state
    $submitButton.prop("disabled", true).text("Adding...");

    // Collect form data
    const formData = {
      action: "music_playlist_add_track",
      nonce: musicPlaylist.nonce,
      playlist_id: $form.find('input[name="playlist_id"]').val(),
      title: $form.find('input[name="title"]').val(),
      artist: $form.find('input[name="artist"]').val(),
      file_url: $form.find('input[name="file_url"]').val(),
      cover_image: $form.find('input[name="cover_image"]').val(),
      duration: 0, // We'll calculate this on the server or client-side
    };

    // Send AJAX request
    $.ajax({
      url: musicPlaylist.ajaxUrl,
      type: "POST",
      data: formData,
      success: function (response) {
        // Re-enable submit button
        $submitButton.prop("disabled", false).text("Add Track");

        if (response.success) {
          // Clear form
          $form.find('input[name="title"]').val("");
          $form.find('input[name="artist"]').val("");
          $form.find('input[name="file_url"]').val("");
          $form.find('input[name="cover_image"]').val("");
          $("#cover-preview").html("");

          // Add new track to the table
          const track = response.data.track;
          const defaultCoverUrl =
            "../wp-content/plugins/music-playlist/assets/images/default-cover.png";
          const coverUrl = track.cover_image
            ? track.cover_image
            : defaultCoverUrl;

          // Check if the table exists, if not, create it
          if ($("#music-playlist-tracks-list").length === 0) {
            const tableHTML = `
                            <table class="wp-list-table widefat fixed striped" id="music-playlist-tracks-table">
                                <thead>
                                    <tr>
                                        <th scope="col" class="column-order"></th>
                                        <th scope="col" class="column-cover">Cover</th>
                                        <th scope="col" class="column-title">Title</th>
                                        <th scope="col" class="column-artist">Artist</th>
                                        <th scope="col" class="column-actions">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="music-playlist-tracks-list"></tbody>
                            </table>
                        `;
            $(".music-playlist-tracks h3").after(tableHTML);
          }

          const newRow = `
                        <tr data-id="${track.id}">
                            <td class="column-order">
                                <span class="dashicons dashicons-menu-alt3"></span>
                            </td>
                            <td class="column-cover">
                                <img src="${coverUrl}" alt="${track.title}" width="40" height="40">
                            </td>
                            <td class="column-title">
                                ${track.title}
                            </td>
                            <td class="column-artist">
                                ${track.artist}
                            </td>
                            <td class="column-actions">
                                <button type="button" class="button play-track" data-file="${track.file_url}">
                                    <span class="dashicons dashicons-controls-play"></span>
                                </button>
                                <button type="button" class="button delete-track" data-id="${track.id}">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </td>
                        </tr>
                    `;

          $("#music-playlist-tracks-list").append(newRow);

          // Remove "no tracks" message if it exists
          $(".music-playlist-tracks > p").remove();

          // Reinitialize sortable
          $("#music-playlist-tracks-list").sortable("refresh");

          // Show success message
          alert(response.data.message);
        } else {
          // Show error message
          alert(response.data.message || "Failed to add track");
        }
      },
      error: function () {
        // Re-enable submit button
        $submitButton.prop("disabled", false).text("Add Track");
        alert("AJAX error");
      },
    });
  });

  // Delete track button
  $(document).on("click", ".delete-track", function () {
    const $button = $(this);
    const trackId = $button.data("id");

    // Confirm delete
    if (!confirm(musicPlaylist.i18n.confirmDelete)) {
      return;
    }

    // Send AJAX request
    $.ajax({
      url: musicPlaylist.ajaxUrl,
      type: "POST",
      data: {
        action: "music_playlist_delete_track",
        nonce: musicPlaylist.nonce,
        track_id: trackId,
      },
      beforeSend: function () {
        // Disable button
        $button.prop("disabled", true);
      },
      success: function (response) {
        if (response.success) {
          // Remove row from table
          $button.closest("tr").fadeOut(300, function () {
            $(this).remove();

            // If no tracks left, show message
            if ($("#music-playlist-tracks-list tr").length === 0) {
              $(".music-playlist-tracks").html(
                "<p>" +
                  "No tracks in this playlist yet. Add your first track above." +
                  "</p>"
              );
            }
          });
        } else {
          // Re-enable button
          $button.prop("disabled", false);
          alert(response.data.message || "Failed to delete track");
        }
      },
      error: function () {
        // Re-enable button
        $button.prop("disabled", false);
        alert("AJAX error");
      },
    });
  });

  // Play track preview
  $(document).on("click", ".play-track", function () {
    const $button = $(this);
    const fileUrl = $button.data("file");
    const $audioPlayer = $("#music-playlist-audio-player");
    const $audio = $("#admin-audio-preview");

    // Set the audio source
    $audio.attr("src", fileUrl);

    // Play the audio
    $audio[0].play();

    // Show the player
    $audioPlayer.addClass("visible");
  });

  // Copy shortcode button
  $(".copy-shortcode").on("click", function () {
    const shortcode = $(this).data("shortcode");

    // Create a temporary input element
    const $temp = $("<input>");
    $("body").append($temp);

    // Set the value and select it
    $temp.val(shortcode).select();

    // Copy the text
    document.execCommand("copy");

    // Remove the temporary element
    $temp.remove();

    // Change button text temporarily
    const $button = $(this);
    const originalText = $button.text();

    $button.text("Copied!");

    setTimeout(function () {
      $button.text(originalText);
    }, 2000);
  });
});
