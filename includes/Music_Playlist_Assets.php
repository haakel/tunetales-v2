<?php
namespace TuneTales_Music;

class Music_Playlist_Assets {
    public function enqueue_admin_scripts($hook) {
        if (!in_array($hook, ['post.php', 'post-new.php'])) return;
        global $post;
        wp_enqueue_media();
        wp_enqueue_script('playlist-admin-js', plugin_dir_url(dirname(__FILE__)) . 'assets/js/playlist-admin.js', ['jquery'], '1.4', true);
        wp_enqueue_style('playlist-admin-css', plugin_dir_url(dirname(__FILE__)) . 'assets/css/playlist-style.css', [], '1.1');
        $all_songs_id = $this->get_all_songs_post_id();
        $playlists = get_posts([
            'post_type' => 'playlist',
            'numberposts' => -1,
            'post_status' => 'publish',
            'post__not_in' => [$all_songs_id],
        ]);
        wp_localize_script('playlist-admin-js', 'playlist_admin_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('playlist_admin_ajax_nonce'),
            'playlists' => array_map(function($p) {
                return ['id' => $p->ID, 'title' => $p->post_title];
            }, $playlists),
            'current_playlist_id' => $post->ID ?? 0,
        ]);
    }

    public function enqueue_custom_post_type_styles() {
        if (is_singular('playlist') || is_post_type_archive('playlist')) {
            wp_enqueue_style('playlist-custom-style', plugin_dir_url(dirname(__FILE__)). 'assets/css/playlist-style.css', [], '1.0');
            wp_enqueue_script('playlist-custom-script', plugin_dir_url(dirname(__FILE__)) . 'assets/js/playlist-script.js', ['jquery'], '1.0', true);
            wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css');
            wp_localize_script('playlist-custom-script', 'tunetales_vars', [
                'archive_url' => get_post_type_archive_link('playlist'),
                'ajaxurl' => admin_url('admin-ajax.php'),
                'plugin_url' => plugin_dir_url(__FILE__),
            ]);
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