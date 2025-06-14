<?php
// تعریف فضای نام برای جلوگیری از تداخل با سایر پلاگین‌ها یا قالب‌ها
namespace TuneTales_Music;

// تعریف کلاس Music_Playlist_AJAX برای مدیریت درخواست‌های AJAX
class Music_Playlist_AJAX {
    /**
     * متد برای ایجاد پلی‌لیست جدید از طریق درخواست AJAX
     */
    public function ajax_create_new_playlist() {
        // بررسی نانس امنیتی برای جلوگیری از حملات CSRF
        check_ajax_referer('playlist_admin_ajax_nonce', 'nonce');

        // دریافت و پاک‌سازی نام پلی‌لیست از داده‌های ارسالی
        $playlist_name = sanitize_text_field($_POST['playlist_name'] ?? '');

        // بررسی خالی نبودن نام پلی‌لیست
        if (empty($playlist_name)) {
            wp_send_json_error(['message' => 'Playlist name is required']);
        }

        // ایجاد پست جدید برای پلی‌لیست
        $post_id = wp_insert_post([
            'post_title'  => $playlist_name, // عنوان پلی‌لیست
            'post_type'   => 'playlist', // نوع پست (پست‌تایپ سفارشی)
            'post_status' => 'publish', // وضعیت انتشار
            'post_author' => get_current_user_id(), // نویسنده (کاربر فعلی)
        ]);

        // بررسی موفقیت‌آمیز بودن ایجاد پست
        if ($post_id && !is_wp_error($post_id)) {
            // ذخیره متادیتا برای مشخص کردن اینکه این پلی‌لیست، پلی‌لیست "همه آهنگ‌ها" نیست
            update_post_meta($post_id, '_is_all_songs_playlist', false);
            // ارسال پاسخ موفقیت‌آمیز با ID و عنوان پلی‌لیست
            wp_send_json_success(['id' => $post_id, 'title' => $playlist_name]);
        } else {
            // ارسال خطا در صورت شکست
            wp_send_json_error(['message' => 'Failed to create playlist']);
        }
    }

    /**
     * متد برای دریافت ID پیوست از URL فایل
     */
    public function ajax_get_attachment_id() {
        // دریافت URL فایل از داده‌های ارسالی
        $url = $_POST['url'] ?? '';
        // تبدیل URL به ID پیوست
        $id = attachment_url_to_postid($url);
        // ارسال پاسخ با ID پیوست
        wp_send_json(['id' => $id]);
    }

    /**
     * متد برای دریافت URL تصویر پیوست (کاور آهنگ)
     */
    public function ajax_get_attachment_url() {
        // دریافت ID پیوست و پاک‌سازی آن
        $id = intval($_POST['id'] ?? 0);
        // دریافت اندازه تصویر (پیش‌فرض: medium)
        $size = $_POST['size'] ?? 'medium';
        // دریافت ID تصویر شاخص پیوست
        $thumbnail_id = get_post_thumbnail_id($id);
        // دریافت URL تصویر شاخص یا تصویر پیش‌فرض در صورت عدم وجود
        $url = $thumbnail_id 
            ? wp_get_attachment_image_url($thumbnail_id, $size) 
            : plugin_dir_url(dirname(__FILE__)) . 'assets/image/default-cover.jpg';
        // ارسال پاسخ با URL تصویر
        wp_send_json(['url' => $url]);
    }

    /**
     * متد برای ذخیره آهنگ در مسیر سفارشی
     */
    public function save_song_to_custom_directory() {
        // بررسی نانس امنیتی
        check_ajax_referer('playlist_admin_ajax_nonce', '_ajax_nonce');

        // دریافت و پاک‌سازی ID آهنگ و پست
        $song_id = intval($_POST['song_id'] ?? 0);
        $post_id = intval($_POST['post_id'] ?? 0);

        // بررسی معتبر بودن درخواست
        if (!$song_id || !$post_id) {
            wp_send_json_error(['message' => 'Invalid request.']);
        }

        // دریافت مسیر فایل آهنگ و اطلاعات پست
        $song_path = get_attached_file($song_id);
        $post = get_post($post_id);
        $upload_dir = wp_upload_dir(); // دریافت اطلاعات دایرکتوری آپلود

        // بررسی معتبر بودن پست و مسیر آهنگ
        if (!$post || !$song_path) {
            wp_send_json_error(['message' => 'Invalid post or song.']);
        }

        // دریافت مسیر سفارشی برای ذخیره آهنگ
        $custom_dir = $this->get_custom_directory($upload_dir, $post->post_title);
        // ایجاد مسیر جدید برای آهنگ
        $new_song_path = $custom_dir . '/' . basename($song_path);

        // بررسی وجود فایل در مسیر جدید
        if (file_exists($new_song_path)) {
            wp_send_json_error(['message' => 'A song with this name already exists.']);
        }

        // کپی فایل به مسیر جدید
        if (!copy($song_path, $new_song_path)) {
            wp_send_json_error(['message' => 'Error copying the file.']);
        }

        // تبدیل مسیر فایل به URL
        $new_song_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $new_song_path);
        // به‌روزرسانی مسیر پیوست در دیتابیس
        update_attached_file($song_id, $new_song_path);
        // ارسال پاسخ موفقیت‌آمیز با URL جدید
        wp_send_json_success(['new_song_url' => $new_song_url]);
    }

    /**
     * متد خصوصی برای دریافت یا ایجاد دایرکتوری سفارشی
     * 
     * @param array $upload_dir اطلاعات دایرکتوری آپلود وردپرس
     * @param string $post_title عنوان پست (برای نام‌گذاری دایرکتوری)
     * @return string مسیر دایرکتوری سفارشی
     */
    private function get_custom_directory($upload_dir, $post_title) {
        // ایجاد مسیر دایرکتوری سفارشی بر اساس عنوان پست
        $custom_dir = $upload_dir['basedir'] . '/playlists/' . sanitize_title($post_title);
        // ایجاد دایرکتوری در صورت عدم وجود
        if (!file_exists($custom_dir)) {
            wp_mkdir_p($custom_dir) || wp_send_json_error(['message' => 'Unable to create directory.']);
        }
        return $custom_dir;
    }
}