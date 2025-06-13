<?php
namespace TuneTales_Music;

class Music_Playlist_Utils {
    public function prevent_all_songs_deletion($post_id) {
        $post = get_post($post_id);
        if ($post->post_type !== 'playlist') {
            return;
        }

        $all_songs_id = $this->get_all_songs_post_id();
        if ($post_id == $all_songs_id) {
            wp_die(__('The "All Songs" playlist cannot be deleted.', 'music-playlist'));
        }
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