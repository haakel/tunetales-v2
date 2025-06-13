<?php
namespace TuneTales_Music;

class Music_Playlist_Saver {
    const META_KEY_SONGS = '_playlist_songs';
    const NONCE_ACTION = 'save_playlist_songs';

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
        if (isset($_POST['post_type']) && 'playlist' === $_POST['post_type'] && !current_user_can('edit_post', $post_id)) return false;
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