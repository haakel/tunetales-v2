<?php
// تعریف فضای نام برای جلوگیری از تداخل با سایر پلاگین‌ها یا قالب‌ها
namespace TuneTales_Music;

// تعریف کلاس Music_Playlist_Assets برای مدیریت بارگذاری اسکریپت‌ها و استایل‌ها
class Music_Playlist_Assets {
    /**
     * متد برای بارگذاری اسکریپت‌ها و استایل‌های پنل مدیریت
     * 
     * @param string $hook نام صفحه فعلی در پنل مدیریت
     */
    public function enqueue_admin_scripts($hook) {
        // بررسی اینکه آیا صفحه فعلی صفحه ویرایش یا ایجاد پست است
        if (!in_array($hook, ['post.php', 'post-new.php'])) {
            return; // خروج در صورت عدم تطابق صفحه
        }

        global $post; // دسترسی به شیء پست فعلی

        // بارگذاری کتابخانه رسانه وردپرس برای انتخاب فایل‌ها
        wp_enqueue_media();

        // بارگذاری اسکریپت مدیریت پلی‌لیست
        wp_enqueue_script(
            'playlist-admin-js', // شناسه اسکریپت
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/playlist-admin.js', // مسیر فایل
            ['jquery'], // وابستگی‌ها
            '1.4', // نسخه
            true // بارگذاری در فوتر
        );

        // بارگذاری استایل‌های مدیریت پلی‌لیست
        wp_enqueue_style(
            'playlist-admin-css', // شناسه استایل
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/playlist-style.css', // مسیر فایل
            [], // بدون وابستگی
            '1.1' // نسخه
        );

        // دریافت ID پست مربوط به پلی‌لیست "همه آهنگ‌ها"
        $all_songs_id = $this->get_all_songs_post_id();

        // دریافت لیست تمام پلی‌لیست‌ها به جز پلی‌لیست "همه آهنگ‌ها"
        $playlists = get_posts([
            'post_type'   => 'playlist', // نوع پست
            'numberposts' => -1, // دریافت همه پست‌ها
            'post_status' => 'publish', // فقط پست‌های منتشرشده
            'post__not_in' => [$all_songs_id], // حذف پلی‌لیست "همه آهنگ‌ها"
        ]);

        // ارسال داده‌ها به اسکریپت جاوااسکریپت
        wp_localize_script('playlist-admin-js', 'playlist_admin_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'), // URL برای درخواست‌های AJAX
            'nonce' => wp_create_nonce('playlist_admin_ajax_nonce'), // نانس امنیتی
            'playlists' => array_map(function($p) {
                // تبدیل پست‌ها به آرایه‌ای با ID و عنوان
                return ['id' => $p->ID, 'title' => $p->post_title];
            }, $playlists),
            'current_playlist_id' => $post->ID ?? 0, // ID پلی‌لیست فعلی
        ]);
    }

    /**
     * متد برای بارگذاری استایل‌ها و اسکریپت‌های فرانت‌اند برای پست‌تایپ پلی‌لیست
     */
    public function enqueue_custom_post_type_styles() {
        // بررسی اینکه آیا صفحه فعلی صفحه تک‌پلی‌لیست یا آرشیو پلی‌لیست است
        if (is_singular('playlist') || is_post_type_archive('playlist')) {
            // بارگذاری استایل‌های سفارشی پلی‌لیست
            wp_enqueue_style(
                'playlist-custom-style', // شناسه استایل
                plugin_dir_url(dirname(__FILE__)) . 'assets/css/playlist-style.css', // مسیر فایل
                [], // بدون وابستگی
                '1.0' // نسخه
            );

            // بارگذاری اسکریپت‌های سفارشی پلی‌لیست
            wp_enqueue_script(
                'playlist-custom-script', // شناسه اسکریپت
                plugin_dir_url(dirname(__FILE__)) . 'assets/js/playlist-script.js', // مسیر فایل
                ['jquery'], // وابستگی‌ها
                '1.0', // نسخه
                true // بارگذاری در فوتر
            );

            // بارگذاری کتابخانه Font Awesome برای آیکون‌ها
            wp_enqueue_style(
                'font-awesome', 
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css'
            );

        // ارسال داده‌ها به اسکریپت جاوااسکریپت
        wp_localize_script('playlist-custom-script', 'tunetales_vars', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('playlist-nonce'),
            'plugin_url' => plugin_dir_url(dirname(__FILE__)),
            'archive_url' => get_post_type_archive_link('playlist'),
        ]);
        }
    }

    /**
     * متد خصوصی برای دریافت ID پست پلی‌لیست "همه آهنگ‌ها"
     * 
     * @return int ID پست یا 0 در صورت عدم وجود
     */
    private function get_all_songs_post_id() {
        // دریافت پست‌هایی که متادیتای '_is_all_songs_playlist' دارند
        $posts = get_posts([
            'post_type'   => 'playlist', // نوع پست
            'meta_key'    => '_is_all_songs_playlist', // کلید متادیتا
            'meta_value'  => true, // مقدار متادیتا
            'numberposts' => 1, // فقط یک پست
            'post_status' => 'publish', // فقط پست‌های منتشرشده
        ]);

        // بازگشت ID پست یا 0 در صورت عدم وجود
        return !empty($posts) ? $posts[0]->ID : 0;
    }
}