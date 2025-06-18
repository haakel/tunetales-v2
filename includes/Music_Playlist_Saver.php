<?php
namespace TuneTales_Music;

class Music_Playlist_Saver {
    const NONCE_ACTION = 'save_playlist_songs';

    public function save_playlist_songs($post_id) {
        if (!$this->can_save($post_id)) {
            error_log('TuneTales: Save playlist songs failed due to invalid nonce or permissions.');
            return;
        }

        $songs = $this->sanitize_songs_data();
        $all_songs_id = $this->get_all_songs_post_id();
        global $wpdb;
        $table_name = $wpdb->prefix . 'playlist_songs';

        // لاگ برای دیباگ داده‌های ارسالی
        error_log('TuneTales: Saving playlist songs for post_id ' . $post_id . ', songs: ' . print_r($songs, true));

        // اگر داده‌ای برای ذخیره وجود ندارد، از حذف روابط جلوگیری کنیم
        if (empty($songs)) {
            error_log('TuneTales: No songs provided, skipping delete and insert.');
            return;
        }

        // حذف روابط قبلی برای پلی‌لیست فعلی
        $wpdb->delete($table_name, ['playlist_id' => $post_id], ['%d']);

        // افزودن روابط جدید
        foreach ($songs as $song) {
            $song_id = $song['attachment_id'];
            // افزودن به پلی‌لیست فعلی
            $wpdb->insert(
                $table_name,
                ['playlist_id' => $post_id, 'song_id' => $song_id],
                ['%d', '%d']
            );

            // افزودن به پلی‌لیست‌های انتخاب‌شده
            foreach ($song['playlists'] as $playlist_id) {
                if ($playlist_id != $post_id && $playlist_id != $all_songs_id) {
                    $exists = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM $table_name WHERE playlist_id = %d AND song_id = %d",
                        $playlist_id, $song_id
                    ));
                    if (!$exists) {
                        $wpdb->insert(
                            $table_name,
                            ['playlist_id' => $playlist_id, 'song_id' => $song_id],
                            ['%d', '%d']
                        );
                    }
                }
            }

            // افزودن به پلی‌لیست "همه آهنگ‌ها"
            if ($all_songs_id) {
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table_name WHERE playlist_id = %d AND song_id = %d",
                    $all_songs_id, $song_id
                ));
                if (!$exists) {
                    $wpdb->insert(
                        $table_name,
                        ['playlist_id' => $all_songs_id, 'song_id' => $song_id],
                        ['%d', '%d']
                    );
                }
            }
        }
    }

    private function can_save($post_id) {
        if (!isset($_POST['playlist_songs_nonce']) || !wp_verify_nonce($_POST['playlist_songs_nonce'], self::NONCE_ACTION)) {
            return false;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return false;
        }
        if (isset($_POST['post_type']) && 'playlist' === $_POST['post_type'] && !current_user_can('edit_post', $post_id)) {
            return false;
        }
        return true;
    }

    private function sanitize_songs_data() {
        $songs = [];
        if (isset($_POST['playlist_songs']['attachment_id']) && is_array($_POST['playlist_songs']['attachment_id'])) {
            foreach ($_POST['playlist_songs']['attachment_id'] as $index => $attachment_id) {
                $playlist_ids = isset($_POST['playlist_songs']['playlists'][$index]) 
                    ? array_map('intval', (array)$_POST['playlist_songs']['playlists'][$index]) 
                    : [];
                $songs[] = [
                    'attachment_id' => intval($attachment_id),
                    'playlists' => $playlist_ids,
                ];
            }
        }
        return $songs;
    }

    private function get_all_songs_post_id() {
        $posts = get_posts([
            'post_type' => 'playlist',
            'meta_key' => '_is_all_songs_playlist',
            'meta_value' => true,
            'numberposts' => 1,
            'post_status' => 'publish',
        ]);
        return !empty($posts) ? $posts[0]->ID : 0;
    }
}