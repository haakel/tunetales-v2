<?php
// تعریف فضای نام برای جلوگیری از تداخل با سایر پلاگین‌ها یا قالب‌ها
namespace TuneTales_Music;

// تعریف کلاس Music_Playlist_Metabox برای مدیریت متاباکس‌های پلی‌لیست
class Music_Playlist_Metabox {
    /**
     * متد برای افزودن متاباکس به پست‌تایپ پلی‌لیست
     */
    public function playlist_meta_box() {
        // افزودن متاباکس به پست‌تایپ 'playlist'
        add_meta_box(
            'playlist_songs', // شناسه متاباکس
            __('Playlist Songs', 'music-playlist'), // عنوان متاباکس (ترجمه‌شده)
            [$this, 'render_meta_box'], // تابع callback برای رندر محتوا
            'playlist' // پست‌تایپ هدف
        );
    }

    /**
     * متد برای رندر محتوای متاباکس
     * 
     * @param WP_Post $post شیء پست فعلی
     */
    public function render_meta_box($post) {
        // افزودن نانس امنیتی برای اعتبارسنجی هنگام ذخیره
        wp_nonce_field('save_playlist_songs', 'playlist_songs_nonce');

        // دریافت آهنگ‌های ذخیره‌شده در متادیتای پست
        $songs = get_post_meta($post->ID, '_playlist_songs', true) ?: [];

        // دریافت ID پلی‌لیست "همه آهنگ‌ها"
        $all_songs_id = $this->get_all_songs_post_id();

        // دریافت تمام پلی‌لیست‌ها به جز پلی‌لیست "همه آهنگ‌ها"
        $playlists = get_posts([
            'post_type'   => 'playlist', // نوع پست
            'numberposts' => -1, // دریافت همه پست‌ها
            'post_status' => 'publish', // فقط پست‌های منتشرشده
            'post__not_in' => [$all_songs_id], // حذف پلی‌لیست "همه آهنگ‌ها"
        ]);
        ?>
<!-- wrapper برای آیتم‌های آهنگ -->
<div id="playlist_songs_wrapper">
    <?php 
            // پیمایش آهنگ‌های ذخیره‌شده
            foreach ($songs as $index => $song) : 
                // بررسی آرایه بودن داده‌های آهنگ
                if (is_array($song)) : 
            ?>
    <div class="playlist_song_item">
        <!-- بخش URL آهنگ -->
        <div class="song-url-wrapper">
            <input type="text" name="playlist_songs[url][]" value="<?php echo esc_attr($song['url']); ?>"
                class="playlist_song_input" readonly />
        </div>
        <!-- بخش اقدامات پلی‌لیست -->
        <div class="playlist-actions">
            <!-- باکس چک‌باکس‌ها برای انتخاب پلی‌لیست‌ها -->
            <div class="playlist-checkboxes">
                <p><?php _e('Select Playlists:', 'music-playlist'); ?></p>
                <div class="checkbox-list">
                    <?php 
                            // پیمایش پلی‌لیست‌ها برای ایجاد چک‌باکس‌ها
                            foreach ($playlists as $playlist) : 
                            ?>
                    <label class="checkbox-item">
                        <input type="checkbox" name="playlist_songs[playlists][<?php echo $index; ?>][]"
                            value="<?php echo $playlist->ID; ?>"
                            <?php echo in_array($playlist->ID, $song['playlists'] ?? []) ? 'checked' : ''; ?> />
                        <?php echo esc_html($playlist->post_title); ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- بخش ایجاد پلی‌لیست جدید -->
            <div class="new-playlist-wrapper">
                <input type="text" class="new_playlist_input"
                    placeholder="<?php _e('New Playlist', 'music-playlist'); ?>" />
                <button type="button" class="button add_new_playlist_button">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Add', 'music-playlist'); ?>
                </button>
            </div>
            <!-- دکمه حذف آهنگ -->
            <button type="button" class="button remove_song_button">
                <span class="dashicons dashicons-trash"></span>
                <?php _e('Remove', 'music-playlist'); ?>
            </button>
        </div>
    </div>
    <?php 
                endif; 
            endforeach; 
            ?>
</div>
<!-- دکمه افزودن چندین آهنگ -->
<p>
    <button type="button" id="add_multiple_songs_button">
        <?php _e('Add Multiple Songs', 'music-playlist'); ?>
    </button>
</p>
<?php
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