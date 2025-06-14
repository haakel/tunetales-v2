<?php
// تعریف فضای نام برای جلوگیری از تداخل با سایر پلاگین‌ها یا قالب‌ها
namespace TuneTales_Music;

// تعریف کلاس Music_Playlist_Saver برای مدیریت ذخیره‌سازی آهنگ‌های پلی‌لیست
class Music_Playlist_Saver {
    // تعریف ثابت‌ها برای کلید متادیتا و اکشن نانس
    const META_KEY_SONGS = '_playlist_songs'; // کلید متادیتا برای ذخیره آهنگ‌ها
    const NONCE_ACTION = 'save_playlist_songs'; // اکشن نانس برای اعتبارسنجی

    /**
     * متد برای ذخیره آهنگ‌های پلی‌لیست
     * 
     * @param int $post_id ID پست فعلی (پلی‌لیست)
     */
    public function save_playlist_songs($post_id) {
        // بررسی شرایط مجاز بودن ذخیره‌سازی
        if (!$this->can_save($post_id)) {
            return; // خروج در صورت عدم مجاز بودن
        }

        // پاک‌سازی و دریافت داده‌های آهنگ‌ها
        $songs = $this->sanitize_songs_data();

        // دریافت ID پلی‌لیست "همه آهنگ‌ها"
        $all_songs_id = $this->get_all_songs_post_id();

        // اطمینان از حضور پلی‌لیست فعلی در آرایه playlists هر آهنگ
        foreach ($songs as &$song) {
            // مقداردهی اولیه آرایه playlists در صورت عدم وجود
            if (!isset($song['playlists']) || !is_array($song['playlists'])) {
                $song['playlists'] = [];
            }
            // افزودن ID پلی‌لیست فعلی به آرایه playlists
            if (!in_array($post_id, $song['playlists'])) {
                $song['playlists'][] = $post_id;
            }
            
            // حذف ID پلی‌لیست "همه آهنگ‌ها" از آرایه playlists
            $song['playlists'] = array_filter($song['playlists'], function($id) use ($all_songs_id) {
                return $id != $all_songs_id; // فیلتر کردن ID پلی‌لیست "همه آهنگ‌ها"
            });
        }
        unset($song); // آزادسازی مرجع برای جلوگیری از تغییرات ناخواسته

        // ذخیره آهنگ‌ها در متادیتای پلی‌لیست فعلی
        update_post_meta($post_id, self::META_KEY_SONGS, $songs);

        // اضافه کردن آهنگ‌ها به پلی‌لیست "همه آهنگ‌ها" (در صورت وجود)
        if ($all_songs_id) {
            // دریافت آهنگ‌های موجود در پلی‌لیست "همه آهنگ‌ها"
            $all_songs = get_post_meta($all_songs_id, self::META_KEY_SONGS, true) ?: [];
            foreach ($songs as $song) {
                $song_exists = false;
                // بررسی وجود آهنگ در پلی‌لیست "همه آهنگ‌ها"
                foreach ($all_songs as $existing_song) {
                    if ($existing_song['url'] === $song['url']) {
                        $song_exists = true;
                        break;
                    }
                }
                // افزودن آهنگ در صورت عدم وجود
                if (!$song_exists) {
                    $all_songs[] = $song;
                }
            }
            // به‌روزرسانی متادیتای پلی‌لیست "همه آهنگ‌ها"
            update_post_meta($all_songs_id, self::META_KEY_SONGS, $all_songs);
        }

        // اضافه کردن آهنگ‌ها به پلی‌لیست‌های انتخاب‌شده
        foreach ($songs as $song) {
            if (!empty($song['playlists'])) {
                foreach ($song['playlists'] as $playlist_id) {
                    // بررسی اینکه پلی‌لیست متفاوت از پلی‌لیست فعلی و "همه آهنگ‌ها" باشد
                    if ($playlist_id != $post_id && $playlist_id != $all_songs_id) {
                        // دریافت آهنگ‌های پلی‌لیست مقصد
                        $playlist_songs = get_post_meta($playlist_id, self::META_KEY_SONGS, true) ?: [];
                        $song_exists = false;
                        // بررسی وجود آهنگ در پلی‌لیست مقصد
                        foreach ($playlist_songs as $existing_song) {
                            if ($existing_song['url'] === $song['url']) {
                                $song_exists = true;
                                break;
                            }
                        }
                        // افزودن آهنگ در صورت عدم وجود
                        if (!$song_exists) {
                            $playlist_songs[] = $song;
                            // به‌روزرسانی متادیتای پلی‌لیست مقصد
                            update_post_meta($playlist_id, self::META_KEY_SONGS, $playlist_songs);
                        }
                    }
                }
            }
        }
    }

    /**
     * متد خصوصی برای بررسی شرایط مجاز بودن ذخیره‌سازی
     * 
     * @param int $post_id ID پست فعلی
     * @return bool آیا ذخیره‌سازی مجاز است یا خیر
     */
    private function can_save($post_id) {
        // بررسی وجود و صحت نانس امنیتی
        if (!isset($_POST['playlist_songs_nonce']) || !wp_verify_nonce($_POST['playlist_songs_nonce'], self::NONCE_ACTION)) {
            return false;
        }
        // جلوگیری از ذخیره‌سازی در حالت اتو-سیو
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return false;
        }
        // بررسی دسترسی کاربر به ویرایش پست و تطابق پست‌تایپ
        if (isset($_POST['post_type']) && 'playlist' === $_POST['post_type'] && !current_user_can('edit_post', $post_id)) {
            return false;
        }
        return true;
    }

    /**
     * متد خصوصی برای پاک‌سازی داده‌های آهنگ‌ها
     * 
     * @return array آرایه پاک‌سازی‌شده آهنگ‌ها
     */
    private function sanitize_songs_data() {
        $songs = [];
        // بررسی وجود داده‌های URL آهنگ‌ها
        if (isset($_POST['playlist_songs']['url'])) {
            foreach ($_POST['playlist_songs']['url'] as $index => $url) {
                // دریافت و پاک‌سازی ID پلی‌لیست‌های مرتبط
                $playlist_ids = isset($_POST['playlist_songs']['playlists'][$index]) 
                    ? array_map('intval', $_POST['playlist_songs']['playlists'][$index]) 
                    : [];
                // افزودن آهنگ به آرایه با URL و پلی‌لیست‌های پاک‌سازی‌شده
                $songs[] = [
                    'url' => esc_url_raw($url), // پاک‌سازی URL
                    'playlists' => $playlist_ids, // ID پلی‌لیست‌ها
                ];
            }
        }
        return $songs;
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