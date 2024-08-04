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

        add_action('add_meta_boxes', array($this, 'playlist_meta_box'));

        add_action('save_post', array($this, 'save_playlist_songs'));

        add_action('admin_enqueue_scripts', array($this,'enqueue_admin_scripts') );
        add_action('wp_ajax_save_song_to_custom_directory', array($this,'save_song_to_custom_directory') );
        add_action('wp_enqueue_scripts', array($this,'enqueue_custom_post_type_styles'));

    }

    function playlist_meta_box() {
        add_meta_box(
            'playlist_songs',
            __('Playlist Songs'),
            array($this,'playlist_meta_box_callback'),
            'playlist'
        );
    }
    
    function playlist_meta_box_callback($post) {
        wp_nonce_field('save_playlist_songs', 'playlist_songs_nonce');
        $songs = get_post_meta($post->ID, '_playlist_songs', true);
        if (!is_array($songs)) {
            $songs = array();
        }
        ?>
<div id="playlist_songs_wrapper">
    <?php
            foreach ($songs as $song) {
                ?>
    <div class="playlist_song_item">
        <input type="text" name="playlist_songs[]" value="<?php echo esc_attr($song); ?>" style="width:80%;" readonly />
        <button type="button" class="remove_song_button">Remove</button>
    </div>
    <?php
            }
            ?>
</div>
<p>
    <button type="button" id="add_song_button"><?php _e('Add Song'); ?></button>
</p>
<?php
    }
    
    
    function save_playlist_songs($post_id) {
        if (!isset($_POST['playlist_songs_nonce']) || !wp_verify_nonce($_POST['playlist_songs_nonce'], 'save_playlist_songs')) {
            return;
        }
    
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
    
        if (isset($_POST['post_type']) && 'playlist' == $_POST['post_type']) {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        }
    
        if (!isset($_POST['playlist_songs'])) {
            return;
        }
    
        $songs = array_map('esc_url_raw', $_POST['playlist_songs']);
        update_post_meta($post_id, '_playlist_songs', $songs);
    }
    
    
    function display_playlist($content) {
        if (is_singular('playlist')) {
            $songs = get_post_meta(get_the_ID(), '_playlist_songs', true);
            if ($songs) {
                $playlist_html = '<ul class="playlist">';
                foreach ($songs as $song) {
                    $playlist_html .= '<li><audio controls><source src="' . esc_url($song) . '" type="audio/mpeg">Your browser does not support the audio element.</audio></li>';
                }
                $playlist_html .= '</ul>';
                $content .= $playlist_html;
            }
        }
        return $content;
    }
    

    function enqueue_admin_scripts($hook) {
        if ($hook != 'post.php' && $hook != 'post-new.php') {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_script('playlist-admin-js', plugin_dir_url(__FILE__) . 'playlist-admin.js', array('jquery'), null, true);
        wp_localize_script('playlist-admin-js', 'playlist_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('playlist_admin_ajax_nonce')
        ));
    }
    function save_song_to_custom_directory() {
        check_ajax_referer('playlist_admin_ajax_nonce', '_ajax_nonce');
    
        if (!isset($_POST['song_id']) || !isset($_POST['post_id'])) {
            wp_send_json_error(array('message' => 'Invalid request.'));
        }
    
        $song_id = intval($_POST['song_id']);
        $post_id = intval($_POST['post_id']);
        $song_path = get_attached_file($song_id);
        $post = get_post($post_id);
        $upload_dir = wp_upload_dir();
    
        if (!$post || !$song_path) {
            wp_send_json_error(array('message' => 'Invalid post or song.'));
        }
    
        $post_title = sanitize_title($post->post_title);
        $custom_dir = $upload_dir['basedir'] . '/playlists/' . $post_title;
    
        // Check if the directory exists, and create it if not
        if (!file_exists($custom_dir)) {
            if (!wp_mkdir_p($custom_dir)) {
                wp_send_json_error(array('message' => 'Unable to create directory: ' . $custom_dir));
            }
        }
    
        $new_song_path = $custom_dir . '/' . basename($song_path);
    
        // Log the paths for debugging
        error_log('Song Path: ' . $song_path);
        error_log('New Song Path: ' . $new_song_path);
    
        // Try copying the file and check for errors
        if (!copy($song_path, $new_song_path)) {
            $error = error_get_last();
            wp_send_json_error(array('message' => 'Error copying the file: ' . $error['message']));
        } else {
            // Update the file URL in the media library
            $new_song_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $new_song_path);
            update_attached_file($song_id, $new_song_path);
            wp_send_json_success(array('new_song_url' => $new_song_url));
        }
    }

    function enqueue_custom_post_type_styles() {
        // Check if we are on a single post of type 'playlist'
        if (is_singular('playlist')) {
            // Enqueue the custom CSS file
            wp_enqueue_style('playlist-custom-style', plugin_dir_url(__FILE__) . 'playlist-style.css');
        }
    }
    
}

music_playlist_admin::get_instance();