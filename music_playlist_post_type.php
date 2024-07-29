<?php
/*
Plugin Name: music palylist
Description: پلاگین ساخت پلی لیست
Author: haakel
*/

if ( ! defined( 'ABSPATH' ) ) {
    echo "what the hell are you doing here?";
	exit;
	}
	
	class music_playlist{
  	/**
	 * Initiator
	 *
	 * @return object Initialized object of class.
     * 
	 */
    private static $instance;

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

    public function __construct(){
        $this->define_constants();
		$this->music_playlist_loader();
        add_action('wp_enqueue_scripts', array($this, 'my_music_plugin_enqueue_scripts'));
        
        add_action('init', array($this,'create_playlist_post_type'));
        add_filter('the_content', array($this,'display_playlist'));
        add_action('add_meta_boxes', array($this,'add_playlist_meta_boxes'));
        add_action('save_post',  array($this,'save_playlist_songs'));
    }
    function save_playlist_songs($post_id) {
        if (array_key_exists('playlist_songs', $_POST)) {
            update_post_meta($post_id, '_playlist_songs', $_POST['playlist_songs']);
        }
    }
    
    
    function create_playlist_post_type() {
        register_post_type('playlist',
            array(
                'labels' => array(
                    'name' => __('Playlists'),
                    'singular_name' => __('Playlist')
                ),
                'public' => true,
                'has_archive' => true,
                'supports' => array('title', 'editor', 'thumbnail')
            )
        );
    }
    function add_playlist_meta_boxes() {
        add_meta_box('playlist_songs', 'Songs',array($this, 'playlist_songs_callback'), 'playlist', 'normal', 'high');
    }
    
    function playlist_songs_callback($post) {
        // متاباکس برای اضافه کردن آهنگ‌ها به پلی‌لیست
        // برای سادگی، فقط یک ورودی برای لینک آهنگ اضافه می‌کنیم
        echo '<input type="text" name="playlist_songs[]" placeholder="Enter song URL">';
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
    

    function my_music_plugin_enqueue_scripts() {

        wp_enqueue_style('my-music-plugin-style', MUSIC_PLAYLIST_ASSETS_URL . 'css/style_music_playlist.css');
        wp_enqueue_script('my-music-plugin-script', MUSIC_PLAYLIST_ASSETS_URL . 'js/script.js', array('jquery'), null, true);
    }

    /**
     * Define all constants
     */
    public function define_constants() {
            define( 'MUSIC_PLAYLIST_VERSION', '1.0.0' );
            define( 'MUSIC_PLAYLIST_FILE', __FILE__ );
            define('MUSIC_PLAYLIST_URL', plugin_dir_url(MUSIC_PLAYLIST_FILE));
            define('MUSIC_PLAYLIST_PATH', plugin_dir_path(MUSIC_PLAYLIST_FILE));
            define( 'MUSIC_PLAYLIST_BASE', plugin_basename( MUSIC_PLAYLIST_FILE ) );
            define( 'MUSIC_PLAYLIST_SLUG', 'music-playlist-settings' );     
            define( 'MUSIC_PLAYLIST_SETTINGS_LINK', admin_url( 'admin.php?page=' . MUSIC_PLAYLIST_SLUG ) );
            define( 'MUSIC_PLAYLIST_CLASSES_PATH', MUSIC_PLAYLIST_PATH . 'classes/' );
            define( 'MUSIC_PLAYLIST_IMAGES', MUSIC_PLAYLIST_PATH . 'build/images' );
            define( 'MUSIC_PLAYLIST_TEMPLATES_PATH', MUSIC_PLAYLIST_PATH . 'templates/' );
            define('MUSIC_PLAYLIST_ASSETS_URL', MUSIC_PLAYLIST_URL . 'assets/');
            
    }
    
	/**
	 * Require loader music playlist class.
	 *
	 * @return void
	 */
    public function music_playlist_loader() {
		require MUSIC_PLAYLIST_CLASSES_PATH .'class_music_playlist_loader.php';
	}

}

music_playlist::get_instance();