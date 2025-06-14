<?php
// تعریف فضای نام برای جلوگیری از تداخل با سایر پلاگین‌ها یا قالب‌ها
namespace TuneTales_Music;

// تعریف کلاس Music_Playlist_Template برای مدیریت قالب‌های سفارشی پست‌تایپ پلی‌لیست
class Music_Playlist_Template {
    /**
     * متد برای بارگذاری قالب‌های سفارشی برای پست‌تایپ پلی‌لیست
     * 
     * @param string $template مسیر قالب پیش‌فرض
     * @return string مسیر قالب سفارشی یا پیش‌فرض
     */
    public function load_custom_template($template) {
        // بررسی اینکه آیا صفحه فعلی یک تک‌پست از نوع پلی‌لیست است
        if (is_singular('playlist')) {
            // بازگشت مسیر قالب سفارشی برای تک‌پلی‌لیست
            return plugin_dir_path(dirname(__FILE__)) . '/template/single-playlist.php';
        } 
        // بررسی اینکه آیا صفحه فعلی آرشیو پست‌تایپ پلی‌لیست است
        elseif (is_post_type_archive('playlist')) {
            // بازگشت مسیر قالب سفارشی برای آرشیو پلی‌لیست
            return plugin_dir_path(dirname(__FILE__)) . '/template/archive-playlist.php';
        }
        // بازگشت قالب پیش‌فرض در صورت عدم تطابق
        return $template;
    }
}