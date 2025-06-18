<?php
// تعریف اطلاعات اصلی پلاگین شامل نام، توضیحات و نویسنده
/*
Plugin Name: TuneTales Music Playlist
Description: پلاگین ساخت پلی‌لیست با پست‌تایپ برای TuneTales
Author: Haakel
*/

// تعریف فضای نام برای جلوگیری از تداخل با سایر پلاگین‌ها یا قالب‌ها
namespace TuneTales_Music;

// بررسی وجود ثابت ABSPATH برای جلوگیری از دسترسی مستقیم به فایل
if (!defined('ABSPATH')) exit('Access denied.');

// بارگذاری فایل‌های مورد نیاز پلاگین
require_once __DIR__ . '/includes/Music_Playlist_Post_Type.php'; // فایل تعریف پست‌تایپ
require_once __DIR__ . '/includes/Music_Playlist_Metabox.php'; // فایل تعریف متاباکس
require_once __DIR__ . '/includes/Music_Playlist_Saver.php'; // فایل ذخیره‌سازی داده‌های پلی‌لیست
require_once __DIR__ . '/includes/Music_Playlist_AJAX.php'; // فایل مدیریت درخواست‌های AJAX
require_once __DIR__ . '/includes/Music_Playlist_Assets.php'; // فایل مدیریت اسکریپت‌ها و استایل‌ها
require_once __DIR__ . '/includes/Music_Playlist_Template.php'; // فایل مدیریت قالب‌های سفارشی
require_once __DIR__ . '/includes/Music_Playlist_Utils.php'; // فایل ابزارهای کمکی
require_once __DIR__ . '/includes/Music_Playlist_Database.php'; // فایل ابزارهای کمکی

// تعریف کلاس اصلی پلاگین
class Music_Playlist_Main {
    // متغیر استاتیک برای پیاده‌سازی الگوی Singleton
    private static $instance;
    
    // متغیرهای خصوصی برای نگهداری نمونه‌های کلاس‌های مختلف
    private $post_type, $metabox, $saver, $ajax, $assets, $template, $utils,$database;

    // متد استاتیک برای دریافت نمونه یکتا از کلاس (الگوی Singleton)
    public static function get_instance() {
        // بررسی وجود نمونه قبلی و ایجاد نمونه جدید در صورت عدم وجود
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // سازنده کلاس برای مقداردهی اولیه اشیاء
    public function __construct() {
        // ایجاد نمونه از کلاس‌های مورد نیاز
        $this->post_type = new Music_Playlist_Post_Type(); // مدیریت پست‌تایپ پلی‌لیست
        $this->metabox = new Music_Playlist_Metabox(); // مدیریت متاباکس‌های پلی‌لیست
        $this->saver = new Music_Playlist_Saver(); // مدیریت ذخیره‌سازی داده‌های پلی‌لیست
        $this->ajax = new Music_Playlist_AJAX(); // مدیریت درخواست‌های AJAX
        $this->assets = new Music_Playlist_Assets(); // مدیریت اسکریپت‌ها و استایل‌ها
        $this->template = new Music_Playlist_Template(); // مدیریت قالب‌های سفارشی
        $this->utils = new Music_Playlist_Utils(); // ابزارهای کمکی
        $this->database = new Music_Playlist_Database(); // ابزارهای کمکی

        // ثبت هوک‌های وردپرس
        $this->register_hooks();
    }

    // متد خصوصی برای ثبت هوک‌های وردپرس
    private function register_hooks() {
        // ثبت هوک‌ها برای مدیریت پست‌تایپ و قابلیت‌های مرتبط
        add_action('init', [$this->post_type, 'create_playlist_post_type']); // ایجاد پست‌تایپ پلی‌لیست
        add_action('init', [$this->post_type, 'enable_thumbnail_for_attachments']); // فعال‌سازی تصویر شاخص برای پیوست‌ها
        add_action('init', [$this->post_type, 'create_all_songs_post']); // ایجاد پست پیش‌فرض برای همه آهنگ‌ها
        add_action('init', [$this->database, 'activate']); // ایجاد پست پیش‌فرض برای همه آهنگ‌ها
        add_action('add_meta_boxes', [$this->metabox, 'playlist_meta_box']); // افزودن متاباکس به صفحه ویرایش پلی‌لیست
        add_action('save_post_playlist', [$this->saver, 'save_playlist_songs']); // ذخیره داده‌های پلی‌لیست هنگام ذخیره پست
        // ثبت هوک‌های AJAX برای مدیریت درخواست‌های ناهمزمان
        add_action('wp_ajax_get_attachment_id', [$this->ajax, 'ajax_get_attachment_id']); // دریافت ID پیوست
        add_action('wp_ajax_get_attachment_url', [$this->ajax, 'ajax_get_attachment_url']); // دریافت URL پیوست
        add_action('wp_ajax_create_new_playlist', [$this->ajax, 'ajax_create_new_playlist']); // ایجاد پلی‌لیست جدید
        add_action('wp_ajax_save_song_to_custom_directory', [$this->ajax, 'save_song_to_custom_directory']); // ذخیره آهنگ در مسیر سفارشی
        // ثبت هوک‌ها برای بارگذاری اسکریپت‌ها و استایل‌ها
        add_action('admin_enqueue_scripts', [$this->assets, 'enqueue_admin_scripts']); // بارگذاری اسکریپت‌های پنل مدیریت
        add_action('wp_enqueue_scripts', [$this->assets, 'enqueue_custom_post_type_styles']); // بارگذاری استایل‌های پست‌تایپ در فرانت‌اند
        // ثبت فیلتر برای استفاده از قالب سفارشی
        add_filter('template_include', [$this->template, 'load_custom_template']); // بارگذاری قالب سفارشی برای پست‌تایپ
        // ثبت هوک‌ها برای جلوگیری از حذف پست پیش‌فرض
        add_action('wp_trash_post', [$this->utils, 'prevent_all_songs_deletion']); // جلوگیری از انتقال پست پیش‌فرض به زباله‌دان
        add_action('before_delete_post', [$this->utils, 'prevent_all_songs_deletion']); // جلوگیری از حذف پست پیش‌فرض
    }
}

// ایجاد نمونه از کلاس اصلی برای فعال‌سازی پلاگین
Music_Playlist_Main::get_instance();