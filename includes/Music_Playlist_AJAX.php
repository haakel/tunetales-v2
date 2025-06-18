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
        check_ajax_referer('playlist_admin_ajax_nonce', 'nonce');
        $url = $_POST['url'] ?? '';
        $id = attachment_url_to_postid($url);
        if ($id) {
            $metadata = [
                'id' => $id,
                'title' => get_the_title($id),
                'artist' => get_post_meta($id, '_song_artist', true) ?: 'Unknown Artist',
                'album' => get_post_meta($id, '_song_album', true) ?: 'Unknown Album',
            ];
            wp_send_json_success($metadata);
        } else {
            wp_send_json_error(['message' => 'Invalid attachment URL']);
        }
    }

    public function ajax_get_attachment_url() {
        error_log("شروع تابع ajax_get_attachment_url");
        error_log("داده‌های POST: " . print_r($_POST, true));

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'playlist-nonce')) {
            error_log("خطای نانس");
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }

        $attachment_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        error_log("Attachment ID: $attachment_id");
        if (!$attachment_id) {
            error_log("خطای ID پیوست");
            wp_send_json_error(['message' => 'Invalid attachment ID']);
            return;
        }

        $size = isset($_POST['size']) ? sanitize_text_field($_POST['size']) : 'medium';
        error_log("اندازه تصویر: $size");

        $url = wp_get_attachment_image_url($attachment_id, $size);
        error_log("URL تصویر: " . ($url ? $url : 'هیچ URL'));
        if ($url) {
            error_log("تصویر گرفته شد: $url");
            wp_send_json_success(['url' => $url]);
        } else {
            error_log("تصویر نداره:");
            wp_send_json_error(['message' => 'Image not found']);
        }
    }   
    public function save_song_metadata() {
        check_ajax_referer('playlist_admin_ajax_nonce', '_ajax_nonce');
        $song_id = intval($_POST['song_id'] ?? 0);
        $artist = sanitize_text_field($_POST['artist'] ?? '');
        $album = sanitize_text_field($_POST['album'] ?? '');

        if (!$song_id) {
            wp_send_json_error(['message' => 'Invalid song ID']);
        }

        $metadata_task = new Metadata_Task();
        $metadata_task->push_to_queue([
            'song_id' => $song_id,
            'artist' => $artist,
            'album' => $album,
        ]);
        $metadata_task->save()->dispatch();

        wp_send_json_success(['message' => 'Metadata save scheduled']);
    }

    public function ajax_get_playlists() {
        check_ajax_referer('playlist_admin_ajax_nonce', 'nonce');
        $all_songs_id = $this->get_all_songs_post_id();
        $playlists = get_posts([
            'post_type' => 'playlist',
            'numberposts' => -1,
            'post_status' => 'publish',
            'post__not_in' => [$all_songs_id],
        ]);

        $playlist_data = array_map(function($playlist) {
            return [
                'id' => $playlist->ID,
                'title' => $playlist->post_title,
            ];
        }, $playlists);

        wp_send_json_success(['playlists' => $playlist_data]);
    }

    private function get_all_songs_post_id() {
        $posts = get_posts([
            'post_type' => 'playlist',
            'meta_key' => '_is_all_songs_playlist',
            'meta_value' => true,
            'numberposts' => 1,
            'post_status' => 'publish',
        ]);
        return !empty($posts) ? $posts[0]->ID : 0;
    }
}