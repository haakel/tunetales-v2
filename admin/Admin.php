<?php
/**
 * Admin functionality
 */
class Admin
{

    private static $instance;
    
    public static function get_instance() {
        if (!isset(self::$instance)) self::$instance = new self();
        return self::$instance;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        // Register admin menu
        add_action('admin_menu', [$this, 'registerAdminMenu']);
        
        // Enqueue admin assets
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        
        // Register AJAX handlers
        // add_action('wp_ajax_music_playlist_add_track', [$this, 'ajaxAddTrack']);
        // add_action('wp_ajax_music_playlist_delete_track', [$this, 'ajaxDeleteTrack']);
        // add_action('wp_ajax_music_playlist_update_track_order', [$this, 'ajaxUpdateTrackOrder']);
    }
    
    /**
     * Register admin menu
     *
     * @return void
     */
    public function registerAdminMenu()
    {
        add_menu_page(
            __('Music Playlist2', 'music-playlist'),
            __('Music Playlist2', 'music-playlist'),
            'manage_options',
            'music-playlist',
            [$this, 'renderAdminPage'],
            'dashicons-playlist-video',
            30
        );
    }
    
    /**
     * Enqueue admin assets
     *
     * @param string $hook
     * @return void
     */
    public function enqueueAssets($hook)
    {
        if ($hook !== 'toplevel_page_music-playlist') {
            return;
        }

        // اصلاح مسیر
        wp_enqueue_style(
            'music-playlist-admin',
            plugins_url('admin.css', __FILE__),
            [],
            '1.0'
        );

        wp_enqueue_media();

        wp_enqueue_script(
            'music-playlist-admin',
            plugins_url('admin.js', __FILE__),
            ['jquery', 'jquery-ui-sortable'],
            '1.0',
            true
        );

        wp_localize_script('music-playlist-admin', 'musicPlaylist', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('music_playlist_nonce'),
            'i18n' => [
                'confirmDelete' => __('Are you sure you want to delete this track?', 'music-playlist'),
                'addTrack' => __('Add Track', 'music-playlist'),
                'selectAudio' => __('Select Audio File', 'music-playlist'),
                'selectCover' => __('Select Cover Image', 'music-playlist'),
            ],
        ]);
    }

    
    /**
     * Render admin page
     *
     * @return void
     */
    public function renderAdminPage()
    {
        include 'main.php';
    }
    
    // /**
    //  * AJAX handler for adding a track
    //  *
    //  * @return void
    //  */
    // public function ajaxAddTrack()
    // {
    //     // Check nonce
    //     if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'music_playlist_nonce')) {
    //         wp_send_json_error(['message' => __('Security check failed', 'music-playlist')]);
    //     }
        
    //     // Check user capabilities
    //     if (!current_user_can('manage_options')) {
    //         wp_send_json_error(['message' => __('Permission denied', 'music-playlist')]);
    //     }
        
    //     // Get and sanitize data
    //     $playlist_id = isset($_POST['playlist_id']) ? intval($_POST['playlist_id']) : 0;
    //     $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    //     $artist = isset($_POST['artist']) ? sanitize_text_field($_POST['artist']) : '';
    //     $file_url = isset($_POST['file_url']) ? esc_url_raw($_POST['file_url']) : '';
    //     $cover_image = isset($_POST['cover_image']) ? esc_url_raw($_POST['cover_image']) : '';
    //     $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 0;
        
    //     // Validate data
    //     if (!$playlist_id || !$title || !$file_url) {
    //         wp_send_json_error(['message' => __('Required fields are missing', 'music-playlist')]);
    //     }
        
    //     // Get max position
    //     global $wpdb;
    //     $table_name = $wpdb->prefix . 'music_playlist_tracks';
    //     $max_position = $wpdb->get_var(
    //         $wpdb->prepare(
    //             "SELECT MAX(position) FROM $table_name WHERE playlist_id = %d",
    //             $playlist_id
    //         )
    //     );
        
    //     $position = $max_position ? intval($max_position) + 1 : 1;
        
    //     // Add track
    //     $result = \MusicPlaylist\Database\Setup::addTrack([
    //         'playlist_id' => $playlist_id,
    //         'title' => $title,
    //         'artist' => $artist,
    //         'file_url' => $file_url,
    //         'cover_image' => $cover_image,
    //         'duration' => $duration,
    //         'position' => $position,
    //     ]);
        
    //     if ($result) {
    //         $track_id = $wpdb->insert_id;
    //         wp_send_json_success([
    //             'message' => __('Track added successfully', 'music-playlist'),
    //             'track' => [
    //                 'id' => $track_id,
    //                 'title' => $title,
    //                 'artist' => $artist,
    //                 'file_url' => $file_url,
    //                 'cover_image' => $cover_image,
    //                 'duration' => $duration,
    //                 'position' => $position,
    //             ],
    //         ]);
    //     } else {
    //         wp_send_json_error(['message' => __('Failed to add track', 'music-playlist')]);
    //     }
    // }
    
    // /**
    //  * AJAX handler for deleting a track
    //  *
    //  * @return void
    //  */
    // public function ajaxDeleteTrack()
    // {
    //     // Check nonce
    //     if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'music_playlist_nonce')) {
    //         wp_send_json_error(['message' => __('Security check failed', 'music-playlist')]);
    //     }
        
    //     // Check user capabilities
    //     if (!current_user_can('manage_options')) {
    //         wp_send_json_error(['message' => __('Permission denied', 'music-playlist')]);
    //     }
        
    //     // Get track ID
    //     $track_id = isset($_POST['track_id']) ? intval($_POST['track_id']) : 0;
        
    //     if (!$track_id) {
    //         wp_send_json_error(['message' => __('Invalid track ID', 'music-playlist')]);
    //     }
        
    //     // Delete track
    //     $result = \MusicPlaylist\Database\Setup::deleteTrack($track_id);
        
    //     if ($result) {
    //         wp_send_json_success(['message' => __('Track deleted successfully', 'music-playlist')]);
    //     } else {
    //         wp_send_json_error(['message' => __('Failed to delete track', 'music-playlist')]);
    //     }
    // }
    
    // /**
    //  * AJAX handler for updating track order
    //  *
    //  * @return void
    //  */
    // public function ajaxUpdateTrackOrder()
    // {
    //     // Check nonce
    //     if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'music_playlist_nonce')) {
    //         wp_send_json_error(['message' => __('Security check failed', 'music-playlist')]);
    //     }
        
    //     // Check user capabilities
    //     if (!current_user_can('manage_options')) {
    //         wp_send_json_error(['message' => __('Permission denied', 'music-playlist')]);
    //     }
        
    //     // Get tracks order
    //     $tracks_order = isset($_POST['tracks_order']) ? $_POST['tracks_order'] : [];
        
    //     if (empty($tracks_order) || !is_array($tracks_order)) {
    //         wp_send_json_error(['message' => __('Invalid tracks order', 'music-playlist')]);
    //     }
        
    //     global $wpdb;
    //     $table_name = $wpdb->prefix . 'music_playlist_tracks';
    //     $success = true;
        
    //     // Update positions
    //     foreach ($tracks_order as $position => $track_id) {
    //         $result = $wpdb->update(
    //             $table_name,
    //             ['position' => intval($position) + 1],
    //             ['id' => intval($track_id)]
    //         );
            
    //         if ($result === false) {
    //             $success = false;
    //         }
    //     }
        
    //     if ($success) {
    //         wp_send_json_success(['message' => __('Track order updated', 'music-playlist')]);
    //     } else {
    //         wp_send_json_error(['message' => __('Failed to update track order', 'music-playlist')]);
    //     }
    // }
}
admin::get_instance();