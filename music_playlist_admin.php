<?php
/*
Plugin Name: TuneTales Music Playlist
Description: پلاگین ساخت پلی‌لیست با پست‌تایپ برای TuneTales
Author: Haakel
*/

if (!defined('ABSPATH')) exit('Access denied.');

class Music_Playlist_Admin {
    private static $instance;
    const META_KEY_SONGS = '_playlist_songs';
    const NONCE_ACTION = 'save_playlist_songs';
    const POST_TYPE = 'playlist';

    public static function get_instance() {
        if (!isset(self::$instance)) self::$instance = new self();
        return self::$instance;
    }

    public function __construct() {
        $this->register_hooks();
        add_action('init', [$this, 'create_playlist_post_type']);
        add_action('init', [$this, 'enable_thumbnail_for_attachments']); // اضافه شده
        add_action('wp_ajax_get_attachment_id', [$this, 'ajax_get_attachment_id']);
        add_action('wp_ajax_get_attachment_url', [$this, 'ajax_get_attachment_url']);
        add_filter('template_include', [$this, 'load_custom_template']);
    }

    public function create_playlist_post_type() {
        register_post_type(self::POST_TYPE, [
            'labels' => ['name' => __('Playlists'), 'singular_name' => __('Playlist')],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail'],
            'rewrite' => ['slug' => 'playlists'],
        ]);
    }

    public function enable_thumbnail_for_attachments() {
        add_post_type_support('attachment', 'thumbnail'); // فعال کردن تصویر شاخص برای attachment
    }

    public function load_custom_template($template) {
        if (is_singular(self::POST_TYPE)) {
            return plugin_dir_path(__FILE__) . 'single-playlist.php';
        } elseif (is_post_type_archive(self::POST_TYPE)) {
            return plugin_dir_path(__FILE__) . 'archive-playlist.php';
        }
        return $template;
    }

    private function register_hooks() {
        add_action('add_meta_boxes', [$this, 'playlist_meta_box']);
        add_action('save_post', [$this, 'save_playlist_songs']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_save_song_to_custom_directory', [$this, 'save_song_to_custom_directory']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_custom_post_type_styles']);
    }

    public function ajax_get_attachment_id() {
        $url = $_POST['url'] ?? '';
        $id = attachment_url_to_postid($url);
        wp_send_json(['id' => $id]);
    }

    public function ajax_get_attachment_url() {
        $id = intval($_POST['id'] ?? 0);
        $size = $_POST['size'] ?? 'medium';
        // به‌جای گرفتن تصویر خود فایل، تصویر شاخص رو می‌گیریم
        $thumbnail_id = get_post_thumbnail_id($id);
        $url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, $size) : plugin_dir_url(__FILE__) . 'default-cover.jpg';
        wp_send_json(['url' => $url]);
    }

    public function playlist_meta_box() {
        add_meta_box('playlist_songs', __('Playlist Songs', 'music-playlist'), [$this, 'render_meta_box'], self::POST_TYPE);
    }

    public function render_meta_box($post) {
        wp_nonce_field(self::NONCE_ACTION, 'playlist_songs_nonce');
        $songs = get_post_meta($post->ID, self::META_KEY_SONGS, true) ?: [];
        ?>
<div id="playlist_songs_wrapper">
    <?php foreach ($songs as $song) : if (is_array($song)) : ?>
    <div class="playlist_song_item">
        <input type="text" name="playlist_songs[]" value="<?php echo esc_attr($song['url']); ?>"
            class="playlist_song_input" readonly />
        <button type="button" class="button remove_song_button"><?php _e('Remove', 'music-playlist'); ?></button>
    </div>
    <?php endif; endforeach; ?>
</div>
<p><button type="button" id="add_song_button"
        class="button button-primary"><?php _e('Add Song', 'music-playlist'); ?></button></p>
<?php
    }

    public function save_playlist_songs($post_id) {
        if (!$this->can_save($post_id)) return;
        $songs = $this->sanitize_songs_data();
        update_post_meta($post_id, self::META_KEY_SONGS, $songs);
    }

    private function can_save($post_id) {
        if (!isset($_POST['playlist_songs_nonce']) || !wp_verify_nonce($_POST['playlist_songs_nonce'], self::NONCE_ACTION)) return false;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return false;
        if (isset($_POST['post_type']) && self::POST_TYPE === $_POST['post_type'] && !current_user_can('edit_post', $post_id)) return false;
        return true;
    }

    private function sanitize_songs_data() {
        $songs = [];
        if (isset($_POST['playlist_songs'])) {
            foreach ($_POST['playlist_songs'] as $index => $url) {
                $songs[] = [
                    'url' => esc_url_raw($url),
                ];
            }
        }
        return $songs;
    }

    public function enqueue_admin_scripts($hook) {
        if (!in_array($hook, ['post.php', 'post-new.php'])) return;
        wp_enqueue_media();
        wp_enqueue_script('playlist-admin-js', plugin_dir_url(__FILE__) . 'playlist-admin.js', ['jquery'], '1.0', true);
        wp_localize_script('playlist-admin-js', 'playlist_admin_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('playlist_admin_ajax_nonce')
        ]);
    }

    public function save_song_to_custom_directory() {
        check_ajax_referer('playlist_admin_ajax_nonce', '_ajax_nonce');
        $song_id = intval($_POST['song_id'] ?? 0);
        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$song_id || !$post_id) wp_send_json_error(['message' => 'Invalid request.']);

        $song_path = get_attached_file($song_id);
        $post = get_post($post_id);
        $upload_dir = wp_upload_dir();
        if (!$post || !$song_path) wp_send_json_error(['message' => 'Invalid post or song.']);

        $custom_dir = $this->get_custom_directory($upload_dir, $post->post_title);
        $new_song_path = $custom_dir . '/' . basename($song_path);
        if (file_exists($new_song_path)) wp_send_json_error(['message' => 'A song with this name already exists.']);
        if (!copy($song_path, $new_song_path)) wp_send_json_error(['message' => 'Error copying the file.']);

        $new_song_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $new_song_path);
        update_attached_file($song_id, $new_song_path);
        wp_send_json_success(['new_song_url' => $new_song_url]);
    }

    private function get_custom_directory($upload_dir, $post_title) {
        $custom_dir = $upload_dir['basedir'] . '/playlists/' . sanitize_title($post_title);
        if (!file_exists($custom_dir)) wp_mkdir_p($custom_dir) || wp_send_json_error(['message' => 'Unable to create directory.']);
        return $custom_dir;
    }

    public function enqueue_custom_post_type_styles() {
        if (is_singular(self::POST_TYPE) || is_post_type_archive(self::POST_TYPE)) {
            wp_enqueue_style('playlist-custom-style', plugin_dir_url(__FILE__) . 'playlist-style.css', [], '1.0');
            wp_enqueue_script('playlist-custom-script', plugin_dir_url(__FILE__) . 'playlist-script.js', ['jquery'], '1.0', true);
            wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css');
            wp_localize_script('playlist-custom-script', 'tunetales_vars', [
                'archive_url' => get_post_type_archive_link(self::POST_TYPE),
                'ajaxurl' => admin_url('admin-ajax.php'),
                'plugin_url' => plugin_dir_url(__FILE__),
            ]);
        }
    }
}

Music_Playlist_Admin::get_instance();