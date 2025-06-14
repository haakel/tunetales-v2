<?php
namespace TuneTales_Music;

class Music_Playlist_Post_Type {
    const POST_TYPE = 'playlist';
    public function create_playlist_post_type() {
        register_post_type('playlist', [
            // 'labels' => [
            //     'name' => __('Playlists'),
            //     'singular_name' => __('Playlist'),
            //     'add_new' => __('Add Playlist'),
            // ],
            // 'public' => true,
            // 'has_archive' => true,
            // 'supports' => ['title', 'editor', 'thumbnail'],
            // 'rewrite' => ['slug' => 'playlists'],

                'labels' => [
                'name' => __('Playlists', 'music-playlist'),
                'singular_name' => __('Playlist', 'music-playlist'),
                'add_new' => __('Add New', 'music-playlist'),
                'add_new_item' => __('Add New Playlist', 'music-playlist'),
                'edit_item' => __('Edit Playlist', 'music-playlist'),
                'new_item' => __('New Playlist', 'music-playlist'),
                'view_item' => __('View Playlist', 'music-playlist'),
                'search_items' => __('Search Playlists', 'music-playlist'),
                'not_found' => __('No playlists found', 'music-playlist'),
                'not_found_in_trash' => __('No playlists found in Trash', 'music-playlist'),
            ],
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-playlist-audio',
            'supports' => ['title', 'thumbnail', 'excerpt'],
            'rewrite' => ['slug' => 'playlists'],
            'show_in_rest' => true,
            
        ]);
    }

    public function enable_thumbnail_for_attachments() {
        add_post_type_support('attachment', 'thumbnail');
    }
    public function create_all_songs_post() {
        $post_id = get_page_by_path('all-songs', OBJECT, self::POST_TYPE);
        if (!$post_id) {
            $post_id = wp_insert_post([
                'post_title' => __('All Songs', 'music-playlist'),
                'post_name' => 'all-songs',
                'post_type' => self::POST_TYPE,
                'post_status' => 'publish',
            ]);
            update_post_meta($post_id, '_is_all_songs_playlist', true);
        }
    }
}