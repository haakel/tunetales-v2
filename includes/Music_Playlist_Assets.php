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
            // wp_enqueue_script(
            //     'playlist-custom-script', // شناسه اسکریپت
            //     plugin_dir_url(dirname(__FILE__)) . 'assets/js/playlist-script.js', // مسیر فایل
            //     ['jquery'], // وابستگی‌ها
            //     '1.0', // نسخه
            //     true // بارگذاری در فوتر
            // );

            // Enqueue le bundle React
            wp_enqueue_script(
                'tunetales-react-player-script',
                plugin_dir_url(dirname(__FILE__)) . 'react-app/dist/bundle.js',
                [], // TODO: Ajouter 'wp-element' si React est dégroupé de WordPress
                '0.1.0',
                true
            );

            // بارگذاری کتابخانه Font Awesome برای آیکون‌ها (peut être géré via React plus tard)
            wp_enqueue_style(
                'font-awesome', 
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css'
            );

            // ارسال داده‌ها به اسکریپت React
            $localized_data = [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_rest'), // Utiliser le nonce REST API si possible, sinon un nonce personnalisé
                'plugin_url' => plugin_dir_url(dirname(dirname(__FILE__))), // URL racine du plugin
                'archive_url' => get_post_type_archive_link('playlist'),
                'is_archive_page' => is_post_type_archive('playlist'),
                'current_post_id' => is_singular('playlist') ? get_the_ID() : null,
            ];

            // Si nous sommes sur une page de playlist unique, passons les données des chansons
            if (is_singular('playlist')) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'playlist_songs'; // Assurez-vous que c'est la bonne table/méthode de stockage
                // Ceci est un exemple basé sur la structure de table précédente.
                // Adaptez ceci à la façon dont les chansons sont réellement stockées et récupérées.
                $raw_songs = $wpdb->get_results($wpdb->prepare(
                    "SELECT song_id FROM $table_name WHERE playlist_id = %d",
                    get_the_ID()
                ));
                
                $songs_data = array_map(function($song_row) {
                    $attachment_id = $song_row->song_id;
                    $attachment = get_post($attachment_id);
                    if (!$attachment) return null;

                    return [
                        'id' => $attachment_id,
                        'src' => wp_get_attachment_url($attachment_id),
                        'title' => get_the_title($attachment_id) ?: 'Unknown Title',
                        'artist' => get_post_meta($attachment_id, '_song_artist', true) ?: 'Unknown Artist',
                        'album' => get_post_meta($attachment_id, '_song_album', true) ?: 'Unknown Album',
                        'description' => $attachment->post_content ?: '',
                        'excerpt' => $attachment->post_excerpt ?: '',
                        // Obtenir l'URL de la miniature (cover art)
                        'cover_art_url' => get_the_post_thumbnail_url($attachment_id, 'medium') ?: plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/image/default-cover.jpg',
                    ];
                }, $raw_songs);

                $localized_data['songs'] = array_filter($songs_data); // Supprimer les chansons nulles si une pièce jointe n'a pas été trouvée
                
                // Passer également les informations de la playlist actuelle
                $current_playlist_post = get_post(get_the_ID());
                if ($current_playlist_post) {
                    $localized_data['current_playlist_info'] = [
                        'id' => $current_playlist_post->ID,
                        'title' => $current_playlist_post->post_title,
                        'thumbnail_url' => get_the_post_thumbnail_url($current_playlist_post->ID, 'medium') ?: plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/image/default-playlist.jpg',
                    ];
                }

            } else if (is_post_type_archive('playlist')) {
                // Pour la page d'archive, passons une liste de toutes les playlists
                $playlists_query = new \WP_Query([
                    'post_type' => 'playlist', 
                    'posts_per_page' => -1,
                    'post_status' => 'publish'
                ]);
                $all_playlists_data = [];
                if ($playlists_query->have_posts()) {
                    while($playlists_query->have_posts()) {
                        $playlists_query->the_post();
                        global $wpdb;
                        // Note: Cette requête N+1 pour le nombre de chansons devrait être optimisée à l'avenir
                        $table_name = $wpdb->prefix . 'playlist_songs'; 
                        $song_count = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM $table_name WHERE playlist_id = %d",
                            get_the_ID()
                        ));
                        $all_playlists_data[] = [
                            'id' => get_the_ID(),
                            'title' => get_the_title(),
                            'permalink' => get_permalink(),
                            'thumbnail_url' => get_the_post_thumbnail_url(get_the_ID(), 'medium') ?: plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/image/default-playlist.jpg',
                            'song_count' => $song_count ?: 0,
                        ];
                    }
                }
                wp_reset_postdata();
                $localized_data['playlists'] = $all_playlists_data;
            }

            wp_localize_script('tunetales-react-player-script', 'tunetalesReact', $localized_data);
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