<?php
namespace TuneTales_Music;

class Music_Playlist_AJAX {
    public function ajax_create_new_playlist() {
        check_ajax_referer('playlist_admin_ajax_nonce', 'nonce');
        $playlist_name = sanitize_text_field($_POST['playlist_name'] ?? '');
        if (empty($playlist_name)) {
            wp_send_json_error(['message' => 'Playlist name is required']);
        }

        $post_id = wp_insert_post([
            'post_title' => $playlist_name,
            'post_type' => 'playlist',
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ]);

        if ($post_id && !is_wp_error($post_id)) {
            update_post_meta($post_id, '_is_all_songs_playlist', false);
            wp_send_json_success(['id' => $post_id, 'title' => $playlist_name]);
        } else {
            wp_send_json_error(['message' => 'Failed to create playlist']);
        }
    }

    public function ajax_get_attachment_id() {
        $url = $_POST['url'] ?? '';
        $id = attachment_url_to_postid($url);
        wp_send_json(['id' => $id]);
    }

    public function ajax_get_attachment_url() {
        $id = intval($_POST['id'] ?? 0);
        $size = $_POST['size'] ?? 'medium';
        $thumbnail_id = get_post_thumbnail_id($id);
        $url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, $size) : plugin_dir_url(dirname(__FILE__)) . 'assets/image/default-cover.jpg';
        wp_send_json(['url' => $url]);
    }

    public function save_song_to_custom_directory() {
        check_ajax_referer('playlist_admin_ajax_nonce', '_ajax_nonce');
        $song_id = intval($_POST['song_id'] ?? 0);
        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$song_id || !$post_id) wp_send_json_error(['message' => 'Invalid request.']);

        $song_path = get_attached_file($song_id);
        $post = get_post($post_id);
        $upload_dir = wp_upload_dir();
        if (!$post || !$song_path) wp_send_json_error(['message' => 'Invalid post or song.']);

        $custom_dir = $this->get_custom_directory($upload_dir, $post->post_title);
        $new_song_path = $custom_dir . '/' . basename($song_path);
        if (file_exists($new_song_path)) wp_send_json_error(['message' => 'A song with this name already exists.']);
        if (!copy($song_path, $new_song_path)) wp_send_json_error(['message' => 'Error copying the file.']);

        $new_song_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $new_song_path);
        update_attached_file($song_id, $new_song_path);
        wp_send_json_success(['new_song_url' => $new_song_url]);
    }

    private function get_custom_directory($upload_dir, $post_title) {
        $custom_dir = $upload_dir['basedir'] . '/playlists/' . sanitize_title($post_title);
        if (!file_exists($custom_dir)) wp_mkdir_p($custom_dir) || wp_send_json_error(['message' => 'Unable to create directory.']);
        return $custom_dir;
    }
}