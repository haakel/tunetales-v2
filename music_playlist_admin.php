<?php
if (!defined('ABSPATH')) {
    echo "what the hell are you doing here?";
    exit;
}

class music_playlist_admin {
    private static $instance;

    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct() {
        add_filter('the_content', array($this, 'display_playlist'));
        add_action('add_meta_boxes', array($this, 'add_playlist_meta_boxes'));
        add_action('save_post', array($this, 'save_playlist_songs'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

    }

    function display_playlist($content) {
        if (is_singular('playlist')) {
            $songs = get_post_meta(get_the_ID(), '_playlist_songs', true);
            if ($songs) {
                $content .= '<ul>';
                foreach ($songs as $song) {
                    $content .= '<li><audio controls><source src="' . esc_url($song) . '" type="audio/mpeg"></audio></li>';
                }
                $content .= '</ul>';
            }
        }
        return $content;
    }

    function add_playlist_meta_boxes() {
        add_meta_box(
            'playlist_songs',
            'Songs',
            array($this, 'playlist_songs_callback'),
            'playlist',
            'normal', 
            'high');
    }

    function playlist_songs_callback($post) {
        wp_nonce_field('save_playlist_songs', 'playlist_songs_nonce'); // اضافه کردن nonce برای امنیت بیشتر
        echo '<label for="playlist_song_file">Upload Song:</label> <br>';
        echo '<input type="file" name="playlist_song_file" id="playlist_song_file">';
    }
    

    function save_playlist_songs($post_id) {
        // بررسی امنیت و مجاز بودن عملیات
        if (!isset($_POST['playlist_songs_nonce']) || !wp_verify_nonce($_POST['playlist_songs_nonce'], 'save_playlist_songs')) {
            return;
        }
    
        // بررسی و ذخیره URL های موجود
        if (array_key_exists('playlist_songs', $_POST)) {
            update_post_meta($post_id, '_playlist_songs', $_POST['playlist_songs']);
        }
    
        // آپلود و ذخیره فایل صوتی
        if (!empty($_FILES['playlist_song_file']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
    
            $uploaded = media_handle_upload('playlist_song_file', $post_id);
    
            if (is_wp_error($uploaded)) {
                // آپلود ناموفق
                wp_die('File upload error: ' . $uploaded->get_error_message());
            } else {
                // افزودن فایل آپلود شده به متا داده‌های پست
                $file_url = wp_get_attachment_url($uploaded);
                $songs = get_post_meta($post_id, '_playlist_songs', true);
                if (!is_array($songs)) {
                    $songs = array();
                }
                $songs[] = $file_url;
                update_post_meta($post_id, '_playlist_songs', $songs);
            }
        }
    }
    
    
    function enqueue_admin_scripts() {
        wp_enqueue_script('jquery');
    }
}

music_playlist_admin::get_instance();