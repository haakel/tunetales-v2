<?php

namespace TuneTales_Music;

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
    }

    private function register_hooks() {
        // هوک‌های مربوط به پست‌تایپ و فعال‌سازی
        add_action('init', [$this, 'create_playlist_post_type']);
        add_action('init', [$this, 'enable_thumbnail_for_attachments']);
        add_action('admin_init', [$this, 'create_all_songs_post']);
        register_activation_hook(__FILE__, [$this, 'create_all_songs_post']);

        // هوک‌های متاباکس و ذخیره
        add_action('add_meta_boxes', [$this, 'playlist_meta_box']);
        add_action('save_post_' . self::POST_TYPE, [$this, 'save_playlist_songs']);

        // هوک‌های AJAX
        add_action('wp_ajax_get_attachment_id', [$this, 'ajax_get_attachment_id']);
        add_action('wp_ajax_get_attachment_url', [$this, 'ajax_get_attachment_url']);
        add_action('wp_ajax_create_new_playlist', [$this, 'ajax_create_new_playlist']);
        add_action('wp_ajax_save_song_to_custom_directory', [$this, 'save_song_to_custom_directory']);

        // هوک‌های استایل و اسکریپت
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_custom_post_type_styles']);

        // هوک‌های قالب
        add_filter('template_include', [$this, 'load_custom_template']);

        // جلوگیری از حذف "All Songs"
        add_action('wp_trash_post', [$this, 'prevent_all_songs_deletion']);
        add_action('before_delete_post', [$this, 'prevent_all_songs_deletion']);
    }

    public function ajax_create_new_playlist() {
        check_ajax_referer('playlist_admin_ajax_nonce', 'nonce');
        $playlist_name = sanitize_text_field($_POST['playlist_name'] ?? '');
        if (empty($playlist_name)) {
            wp_send_json_error(['message' => 'Playlist name is required']);
        }

        $post_id = wp_insert_post([
            'post_title'    => $playlist_name,
            'post_type'     => self::POST_TYPE,
            'post_status'   => 'publish',
            'post_author'   => get_current_user_id(),
        ]);

        if ($post_id && !is_wp_error($post_id)) {
            update_post_meta($post_id, '_is_all_songs_playlist', false);
            wp_send_json_success([
                'id' => $post_id,
                'title' => $playlist_name,
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to create playlist']);
        }
    }

    public function create_all_songs_post() {
        $post_id = get_page_by_path('all-songs', OBJECT, self::POST_TYPE);
        if (!$post_id) {
            $post_id = wp_insert_post([
                'post_title' => __('All Songs', 'music-playlist'),
                'post_name' => 'all-songs',
                'post_type' => self::POST_TYPE,
                'post_status' => 'publish',
            ]);
            update_post_meta($post_id, '_is_all_songs_playlist', true);
        }
    }

    public function prevent_all_songs_deletion($post_id) {
        $post = get_post($post_id);
        if ($post->post_type !== self::POST_TYPE) {
            return;
        }

        $all_songs_id = $this->get_all_songs_post_id();
        if ($post_id == $all_songs_id) {
            wp_die(__('The "All Songs" playlist cannot be deleted.', 'music-playlist'));
        }
    }

function create_playlist_post_type() {
    register_post_type('playlist', [
        'labels' => [
            'name' => __('Playlists'),
            'singular_name' => __('Playlist'),
            'add_new' => __('Add Playlist'),
        ],
        'public' => true,
        'has_archive' => true,
        'supports' => ['title', 'editor', 'thumbnail'],
        'rewrite' => ['slug' => 'playlists'],
    ]);
}

    public function enable_thumbnail_for_attachments() {
        add_post_type_support('attachment', 'thumbnail');
    }

    public function load_custom_template($template) {
        if (is_singular(self::POST_TYPE)) {
            return plugin_dir_path(__FILE__) . 'single-playlist.php';
        } elseif (is_post_type_archive(self::POST_TYPE)) {
            return plugin_dir_path(__FILE__) . 'archive-playlist.php';
        }
        return $template;
    }

    public function ajax_get_attachment_id() {
        $url = $_POST['url'] ?? '';
        $id = attachment_url_to_postid($url);
        wp_send_json(['id' => $id]);
    }

    public function ajax_get_attachment_url() {
        $id = intval($_POST['id'] ?? 0);
        $size = $_POST['size'] ?? 'medium';
        $thumbnail_id = get_post_thumbnail_id($id);
        $url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, $size) : plugin_dir_url(dirname(__FILE__)) . 'default-cover.jpg';
        wp_send_json(['url' => $url]);
    }

    public function playlist_meta_box() {
        add_meta_box('playlist_songs', __('Playlist Songs', 'music-playlist'), [$this, 'render_meta_box'], self::POST_TYPE);
    }

    public function render_meta_box($post) {
        wp_nonce_field(self::NONCE_ACTION, 'playlist_songs_nonce');
        $songs = get_post_meta($post->ID, self::META_KEY_SONGS, true) ?: [];
        $all_songs_id = $this->get_all_songs_post_id();
        $playlists = get_posts([
            'post_type' => self::POST_TYPE,
            'numberposts' => -1,
            'post_status' => 'publish',
            'post__not_in' => [$all_songs_id],
        ]);
        ?>
// <div id="playlist_songs_wrapper">
    // <?php foreach ($songs as $index => $song) : if (is_array($song)) : ?>
    // <div class="playlist_song_item">
        // <div class="song-url-wrapper">
            // <input type="text" name="playlist_songs[url][]" value="<?php echo esc_attr($song['url']); ?>" //
                class="playlist_song_input" readonly />
            // </div>
        // <div class="playlist-actions">
            // <div class="playlist-checkboxes">
                // <p><?php _e('Select Playlists:', 'music-playlist'); ?></p>
                // <div class="checkbox-list">
                    // <?php foreach ($playlists as $playlist) : ?>
                    // <label class="checkbox-item">
                        // <input type="checkbox" name="playlist_songs[playlists][<?php echo $index; ?>][]" //
                            value="<?php echo $playlist->ID; ?>" //
                            <?php echo in_array($playlist->ID, $song['playlists'] ?? []) ? 'checked' : ''; ?> />
                        // <?php echo esc_html($playlist->post_title); ?>
                        // </label>
                    // <?php endforeach; ?>
                    // </div>
                // </div>
            // <div class="new-playlist-wrapper">
                // <input type="text" class="new_playlist_input" //
                    placeholder="<?php _e('New Playlist', 'music-playlist'); ?>" />
                // <button type="button" class="button add_new_playlist_button">
                    // <span class="dashicons dashicons-plus-alt"></span> <?php _e('Add', 'music-playlist'); ?>
                    // </button>
                // </div>
            <button type="button" class="button remove_song_button">
                <span class="dashicons dashicons-trash"></span> <?php _e('Remove', 'music-playlist'); ?>
            </button>
        </div>
    </div>
    <?php endif; endforeach; ?>
</div>
<p><button type="button" id="add_multiple_songs_button">
        <?php _e('Add Multiple Songs', 'music-playlist'); ?>
    </button></p>
<?php
    }

    private function get_all_songs_post_id() {
        $posts = get_posts([
            'post_type' => self::POST_TYPE,
            'meta_key' => '_is_all_songs_playlist',
            'meta_value' => true,
            'numberposts' => 1,
            'post_status' => 'publish',
        ]);
        return !empty($posts) ? $posts[0]->ID : 0;
    }

    public function save_playlist_songs($post_id) {
        if (!$this->can_save($post_id)) return;
        $songs = $this->sanitize_songs_data();
        $all_songs_id = $this->get_all_songs_post_id();

        // مطمئن می‌شیم پلی‌لیست فعلی توی آرایه playlists هر آهنگ باشه
        foreach ($songs as &$song) {
            if (!isset($song['playlists']) || !is_array($song['playlists'])) {
                $song['playlists'] = [];
            }
            if (!in_array($post_id, $song['playlists'])) {
                $song['playlists'][] = $post_id;
            }
            // حذف "همه آهنگ‌ها" از آرایه playlists (چون توی UI نباید نمایش داده بشه)
            $song['playlists'] = array_filter($song['playlists'], function($id) use ($all_songs_id) {
                return $id != $all_songs_id;
            });
        }
        unset($song);

        // ذخیره آهنگ‌ها توی پلی‌لیست فعلی
        update_post_meta($post_id, self::META_KEY_SONGS, $songs);

        // اضافه کردن آهنگ‌ها به پلی‌لیست "All Songs"
        if ($all_songs_id) {
            $all_songs = get_post_meta($all_songs_id, self::META_KEY_SONGS, true) ?: [];
            foreach ($songs as $song) {
                $song_exists = false;
                foreach ($all_songs as $existing_song) {
                    if ($existing_song['url'] === $song['url']) {
                        $song_exists = true;
                        break;
                    }
                }
                if (!$song_exists) {
                    $all_songs[] = $song;
                }
            }
            update_post_meta($all_songs_id, self::META_KEY_SONGS, $all_songs);
        }

        // اضافه کردن به پلی‌لیست‌های انتخاب‌شده
        foreach ($songs as $song) {
            if (!empty($song['playlists'])) {
                foreach ($song['playlists'] as $playlist_id) {
                    if ($playlist_id != $post_id && $playlist_id != $all_songs_id) {
                        $playlist_songs = get_post_meta($playlist_id, self::META_KEY_SONGS, true) ?: [];
                        $song_exists = false;
                        foreach ($playlist_songs as $existing_song) {
                            if ($existing_song['url'] === $song['url']) {
                                $song_exists = true;
                                break;
                            }
                        }
                        if (!$song_exists) {
                            $playlist_songs[] = $song;
                            update_post_meta($playlist_id, self::META_KEY_SONGS, $playlist_songs);
                        }
                    }
                }
            }
        }
    }

    private function can_save($post_id) {
        if (!isset($_POST['playlist_songs_nonce']) || !wp_verify_nonce($_POST['playlist_songs_nonce'], self::NONCE_ACTION)) return false;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return false;
        if (isset($_POST['post_type']) && self::POST_TYPE === $_POST['post_type'] && !current_user_can('edit_post', $post_id)) return false;
        return true;
    }

    private function sanitize_songs_data() {
        $songs = [];
        if (isset($_POST['playlist_songs']['url'])) {
            foreach ($_POST['playlist_songs']['url'] as $index => $url) {
                $playlist_ids = isset($_POST['playlist_songs']['playlists'][$index]) 
                    ? array_map('intval', $_POST['playlist_songs']['playlists'][$index]) 
                    : [];
                $songs[] = [
                    'url' => esc_url_raw($url),
                    'playlists' => $playlist_ids,
                ];
            }
        }
        return $songs;
    }

    public function enqueue_admin_scripts($hook) {
        if (!in_array($hook, ['post.php', 'post-new.php'])) return;
        global $post;
        wp_enqueue_media();
        wp_enqueue_script('playlist-admin-js', plugin_dir_url(__FILE__) . 'playlist-admin.js', ['jquery'], '1.4', true);
        wp_enqueue_style('playlist-admin-css', plugin_dir_url(__FILE__) . 'playlist-style.css', [], '1.1');
        $all_songs_id = $this->get_all_songs_post_id();
        $playlists = get_posts([
            'post_type' => self::POST_TYPE,
            'numberposts' => -1,
            'post_status' => 'publish',
            'post__not_in' => [$all_songs_id],
        ]);
        wp_localize_script('playlist-admin-js', 'playlist_admin_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('playlist_admin_ajax_nonce'),
            'playlists' => array_map(function($p) {
                return ['id' => $p->ID, 'title' => $p->post_title];
            }, $playlists),
            'current_playlist_id' => $post->ID ?? 0,
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
            wp_localize_script('playlist-script', 'tunetales_vars', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('playlist-nonce'),
                'plugin_url' => plugin_dir_url(dirname(__FILE__)),
                'archive_url' => get_post_type_archive_link('playlist'),
            ]);
        }
    }
}

Music_Playlist_Admin::get_instance();