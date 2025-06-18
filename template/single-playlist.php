<?php
// single-playlist.php
if (!defined('ABSPATH')) exit('Access denied.');
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>

<body <?php body_class('tunetales-player'); ?>>
    <?php
    global $wpdb;
    $table_name = $wpdb->prefix . 'playlist_songs';
    $songs = $wpdb->get_results($wpdb->prepare(
        "SELECT song_id FROM $table_name WHERE playlist_id = %d",
        get_the_ID()
    ));

    // تبدیل به آرایه‌ای سازگار با playlist-template.php
    $songs_array = array_map(function($song) {
        return ['attachment_id' => $song->song_id];
    }, $songs);

    include 'playlist-template.php';
    wp_footer();
    ?>
</body>

</html>