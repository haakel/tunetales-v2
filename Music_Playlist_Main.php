<?php
/*
Plugin Name: TuneTales Music Playlist
Description: پلاگین ساخت پلی‌لیست با پست‌تایپ برای TuneTales
Author: Haakel
*/
namespace TuneTales_Music;

if (!defined('ABSPATH')) exit('Access denied.');
require_once __DIR__ . '/includes/Music_Playlist_Post_Type.php';
require_once __DIR__ . '/includes/Music_Playlist_Metabox.php';
require_once __DIR__ . '/includes/Music_Playlist_Saver.php';
require_once __DIR__ . '/includes/Music_Playlist_AJAX.php';
require_once __DIR__ . '/includes/Music_Playlist_Assets.php';
require_once __DIR__ . '/includes/Music_Playlist_Template.php';
require_once __DIR__ . '/includes/Music_Playlist_Utils.php';

class Music_Playlist_Main {
    private static $instance;
    private $post_type, $metabox, $saver, $ajax, $assets, $template, $utils;

    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->post_type = new Music_Playlist_Post_Type();
        $this->metabox = new Music_Playlist_Metabox();
        $this->saver = new Music_Playlist_Saver();
        $this->ajax = new Music_Playlist_AJAX();
        $this->assets = new Music_Playlist_Assets();
        $this->template = new Music_Playlist_Template();
        $this->utils = new Music_Playlist_Utils();
        $this->register_hooks();
    }

    private function register_hooks() {
        add_action('init', [$this->post_type, 'create_playlist_post_type']);
        add_action('init', [$this->post_type, 'enable_thumbnail_for_attachments']);
        add_action('add_meta_boxes', [$this->metabox, 'playlist_meta_box']);
        add_action('save_post_playlist', [$this->saver, 'save_playlist_songs']);
        add_action('wp_ajax_get_attachment_id', [$this->ajax, 'ajax_get_attachment_id']);
        add_action('wp_ajax_get_attachment_url', [$this->ajax, 'ajax_get_attachment_url']);
        add_action('wp_ajax_create_new_playlist', [$this->ajax, 'ajax_create_new_playlist']);
        add_action('wp_ajax_save_song_to_custom_directory', [$this->ajax, 'save_song_to_custom_directory']);
        add_action('admin_enqueue_scripts', [$this->assets, 'enqueue_admin_scripts']);
        add_action('wp_enqueue_scripts', [$this->assets, 'enqueue_custom_post_type_styles']);
        add_filter('template_include', [$this->template, 'load_custom_template']);
        add_action('wp_trash_post', [$this->utils, 'prevent_all_songs_deletion']);
        add_action('before_delete_post', [$this->utils, 'prevent_all_songs_deletion']);
    }
}

Music_Playlist_Main::get_instance();