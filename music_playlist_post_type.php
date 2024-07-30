<?php
/*
Plugin Name: music palylist post type
Description:  پلاگین ساخت پلی لیست با پست تایپ
Author: haakel
*/

if ( ! defined( 'ABSPATH' ) ) {
    echo "what the hell are you doing here?";
	exit;
	}
	
	class music_playlist_post_type{
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
        require_once  'music_playlist_admin.php';
        add_action('init', array($this,'create_playlist_post_type'));

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

}

music_playlist_post_type::get_instance();