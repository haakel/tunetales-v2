<?php
// archive-playlist.php
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
    $songs_array = []; // استفاده از songs_array برای سازگاری
    $is_archive = true;
    include 'playlist-template.php';
    wp_footer();
    ?>
</body>

</html>