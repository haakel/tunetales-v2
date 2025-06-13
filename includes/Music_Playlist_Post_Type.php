<?php
namespace TuneTales_Music;

class Music_Playlist_Post_Type {
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
}