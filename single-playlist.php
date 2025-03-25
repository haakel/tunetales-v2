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
    $songs = get_post_meta(get_the_ID(), '_playlist_songs', true);
    include 'playlist-template.php';
    wp_footer();
    ?>
</body>

</html>