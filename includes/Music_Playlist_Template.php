<?php
namespace TuneTales_Music;

class Music_Playlist_Template {
    public function load_custom_template($template) {
        if (is_singular('playlist')) {
            return plugin_dir_path(dirname(__FILE__)) . '/template/single-playlist.php';
        } elseif (is_post_type_archive('playlist')) {
            return plugin_dir_path(dirname(__FILE__)) . '/template/archive-playlist.php';
        }
        return $template;
    }
}