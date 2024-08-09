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
    <?php foreach ($songs as $song): ?>
    <?php if (is_array($song)): ?>
    <div class="playlist_song_item">
        <input type="text" name="playlist_songs[]" value="<?php echo esc_attr($song['url']); ?>"
            class="playlist_song_input" readonly />
        <input type="text" name="playlist_song_titles[]" value="<?php echo esc_attr($song['title']); ?>"
            placeholder="Song Title" class="playlist_song_title_input" />
        <input type="text" name="playlist_song_artists[]" value="<?php echo esc_attr($song['artist']); ?>"
            placeholder="Artist" class="playlist_song_artist_input" />
        <button type="button" class="button remove_song_button">Remove</button>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>
</div>
<p>
    <button type="button" id="add_song_button" class="button button-primary"><?php _e('Add Song'); ?></button>
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
    
        $songs = array();
        if (isset($_POST['playlist_songs'])) {
            foreach ($_POST['playlist_songs'] as $index => $url) {
                $songs[] = array(
                    'url' => esc_url_raw($url),
                    'title' => sanitize_text_field($_POST['playlist_song_titles'][$index]),
                    'artist' => sanitize_text_field($_POST['playlist_song_artists'][$index]),
                    );
            }
            update_post_meta($post_id, '_playlist_songs', $songs);
        }
    }
    
    
    
    function display_playlist($content) {
        if (is_singular('playlist')) {
            $songs = get_post_meta(get_the_ID(), '_playlist_songs', true);
            if ($songs) {     
                $playlist_html = '<div class="music-player">';
                $playlist_html .= '<div class="player-controls">';
                $playlist_html .= '<button class="prev"><i class="fas fa-forward"></i></button>';
                $playlist_html .= '<button class="play-pause"><i class="fas fa-play"></i></button>';
                $playlist_html .= '<button class="next"><i class="fas fa-backward"></i></button>';
                $playlist_html .= '<button class="shuffle"><i class="fas fa-random"></i></button>';
                $playlist_html .= '<input type="range" class="volume" min="0" max="1" step="0.01">';
                $playlist_html .= '<span class="volume-value">100</span>';
                $playlist_html .= '<span class="current-time">00:00</span>';
                $playlist_html .= '<input type="range" class="seekbar" value="0">';
                $playlist_html .= '<span class="duration-time">00:00</span>';
                $playlist_html .= '</div>';
                $playlist_html .= '<ul class="playlist">';
                foreach ($songs as $song) {
                    $playlist_html .= '<li class="playlist_item" data-src="' . esc_url($song['url']) . '">';
                    $playlist_html .= '<span class="song-title">' . esc_html($song['title']) . ' - ' . esc_html($song['artist']) . '</span>';
                    $playlist_html .= '<a href="' . esc_url($song['url']) . '" class="download-song" download><i class="fas fa-download"></i></a>'; // دکمه دانلود
                    $playlist_html .= '</li>';
                }
                $playlist_html .= '</ul>';
                $playlist_html .= '</div>';
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
    
        // Check if a song with the same name already exists in the directory
        if (file_exists($new_song_path)) {
            wp_send_json_error(array('message' => 'A song with this name already exists.'));
        }
    
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
            // Enqueue the custom JS file
            wp_enqueue_script('playlist-custom-script', plugin_dir_url(__FILE__) . 'playlist-script.js', array('jquery'), null, true);
        }
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css');

    }
    
}

music_playlist_admin::get_instance();