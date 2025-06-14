<?php
// تعریف فضای نام برای جلوگیری از تداخل با سایر پلاگین‌ها یا قالب‌ها
namespace TuneTales_Music;

// تعریف کلاس Music_Playlist_Utils برای ارائه ابزارهای کمکی مرتبط با پلی‌لیست
class Music_Playlist_Utils {
    /**
     * متد برای جلوگیری از حذف پلی‌لیست "همه آهنگ‌ها"
     * 
     * @param int $post_id ID پست در حال حذف
     */
    public function prevent_all_songs_deletion($post_id) {
        // دریافت شیء پست با استفاده از ID
        $post = get_post($post_id);

        // بررسی اینکه آیا پست از نوع پلی‌لیست است
        if ($post->post_type !== 'playlist') {
            return; // خروج در صورت عدم تطابق نوع پست
        }

        // دریافت ID پلی‌لیست "همه آهنگ‌ها"
        $all_songs_id = $this->get_all_songs_post_id();

        // بررسی اینکه آیا پست در حال حذف، پلی‌لیست "همه آهنگ‌ها" است
        if ($post_id == $all_songs_id) {
            // توقف عملیات و نمایش پیام خطا
            wp_die(__('The "All Songs" playlist cannot be deleted.', 'music-playlist'));
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