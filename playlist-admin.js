jQuery(document).ready(function ($) {
    $('#add_song_button').on('click', function (e) {
        e.preventDefault();
        var file_frame = wp.media.frames.file_frame = wp.media({
            title: 'Select or Upload Song',
            button: {
                text: 'Select Song'
            },
            multiple: false
        });

        file_frame.on('select', function () {
            var attachment = file_frame.state().get('selection').first().toJSON();
            var song_url = attachment.url;
            var song_id = attachment.id;

            var data = {
                action: 'save_song_to_custom_directory',
                song_id: song_id,
                post_id: $('#post_ID').val(),
                _ajax_nonce: playlist_admin_ajax.nonce
            };

            $.post(playlist_admin_ajax.ajax_url, data, function (response) {
                if (response.success) {
                    var new_song_url = response.data.new_song_url;
                    $('#playlist_songs_wrapper').append('<div class="playlist_song_item"><input type="text" name="playlist_songs[]" value="' + new_song_url + '" style="width:80%;" readonly/><button type="button" class="remove_song_button">Remove</button></div>');
                } else {
                    alert('Error: ' + response.data.message);
                }
            });
        });

        file_frame.open();
    });

    $(document).on('click', '.remove_song_button', function (e) {
        e.preventDefault();
        $(this).closest('.playlist_song_item').remove();
    });
});
