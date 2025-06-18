<?php
namespace TuneTales_Music;

class Music_Playlist_Database {
    public static function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'playlist_songs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            playlist_id BIGINT(20) UNSIGNED NOT NULL,
            song_id BIGINT(20) UNSIGNED NOT NULL,
            PRIMARY KEY (id),
            INDEX playlist_id (playlist_id),
            INDEX song_id (song_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
register_activation_hook(__FILE__, [Music_Playlist_Database::class, 'activate']);