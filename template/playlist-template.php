<?php
// لاگ برای دیباگ آرایه songs_array
// error_log('TuneTales: songs_array = ' . print_r($songs_array, true));

// $songs = isset($songs_array) ? $songs_array : [];
// $is_archive = is_post_type_archive('playlist');
?>
<div id="tunetales-react-player-root">
    <!-- L'application React sera rendue ici -->
</div>

<?php
// Le reste du contenu original est conservé pour l'instant.
// Il sera remplacé ou supprimé progressivement à mesure que les composants React seront développés.
?>
<div class="webapp-player" id="tunetales-player">
    <div class="webapp-player">
        <header class="app-header">
            <button class="sidebar-toggle" aria-label="Toggle Sidebar"><i class="fas fa-bars"></i></button>
            <h1 class="app-title">TuneTales</h1>
            <button class="back-to-archive" aria-label="Back to Archive"><i class="fas fa-arrow-left"></i>
                Archive</button>
        </header>
        <?php if (is_singular('playlist') && $songs) : ?>
        <div class="player-main">
            <div class="sidebar">
                <ul class="playlist" role="listbox">
                    <?php foreach ($songs as $index => $song) : 
                    $attachment_id = $song['attachment_id']; // اصلاح از song_id
                    $url = wp_get_attachment_url($attachment_id);
                    $title = get_the_title($attachment_id) ?: 'Unknown Title';
                    $artist = get_post_meta($attachment_id, '_song_artist', true) ?: 'Unknown Artist';
                    $album = get_post_meta($attachment_id, '_song_album', true) ?: 'Unknown Album';
                    $attachment = get_post($attachment_id);
                    $description = $attachment ? $attachment->post_content : '';
                    $excerpt = $attachment ? $attachment->post_excerpt : '';
                ?>
                    <li class="playlist_item" data-src="<?php echo esc_url($url); ?>"
                        data-attachment-id="<?php echo esc_attr($attachment_id); ?>"
                        data-title="<?php echo esc_attr($title); ?>" data-artist="<?php echo esc_attr($artist); ?>"
                        data-album="<?php echo esc_attr($album); ?>"
                        data-description="<?php echo esc_attr($description); ?>"
                        data-excerpt="<?php echo esc_attr($excerpt); ?>" role="option" tabindex="0">
                        <span class="song-title"><?php echo esc_html($title); ?> -
                            <?php echo esc_html($artist); ?></span>
                        <a href="<?php echo esc_url($url); ?>" class="download-song" download
                            aria-label="Download <?php echo esc_attr($title); ?>">
                            <i class="fas fa-download"></i>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="main-content">
                <div class="now-playing">
                    <?php
                    $current_song = $songs[0] ?? [];
                    $attachment_id = $current_song['attachment_id'] ?? 0; // اصلاح از song_id
                    $url = wp_get_attachment_url($attachment_id);
                    $thumbnail_id = get_post_thumbnail_id($attachment_id);
                    $thumbnail = $thumbnail_id 
                        ? wp_get_attachment_image_url($thumbnail_id, 'medium') 
                        : plugin_dir_url(dirname(__FILE__)) . 'assets/image/default-cover.jpg';
                    $title = get_the_title($attachment_id) ?: 'Unknown Title';
                    $artist = get_post_meta($attachment_id, '_song_artist', true) ?: 'Unknown Artist';
                    $album = get_post_meta($attachment_id, '_song_album', true) ?: 'Unknown Album';
                    $attachment = get_post($attachment_id);
                    $description = $attachment ? $attachment->post_content : '';
                    $excerpt = $attachment ? $attachment->post_excerpt : '';
                ?>
                    <img src="<?php echo esc_url($thumbnail); ?>" alt="Now Playing Cover" class="cover-art">
                    <div class="song-info">
                        <span class="song-title"><?php echo esc_html($title); ?></span>
                        <span class="song-artist"><?php echo esc_html($artist); ?></span>
                        <span class="song-album"><?php echo esc_html($album); ?></span>
                        <p class="song-excerpt"><?php echo esc_html($excerpt); ?></p>
                        <p class="song-description"><?php echo esc_html($description); ?></p>
                    </div>
                </div>
                <div class="player-controls">
                    <div class="range-container">
                        <div class="time">
                            <div class="seekbar-container">
                                <span class="current-time" aria-label="Current Time">00:00</span>
                                <input type="range" class="seekbar" min="0" value="0" aria-label="Seek Bar"
                                    style="--value: 0%;">
                                <span class="duration-time" aria-label="Duration">00:00</span>
                            </div>
                        </div>
                        <div class="volume">
                            <input type="range" class="volume-slider" min="0" max="1" step="0.01" value="1"
                                aria-label="Volume Slider">
                            <span class="volume-display">100</span>
                        </div>
                    </div>
                    <div class="control-buttons">
                        <button class="shuffle" aria-label="Shuffle Playlist"><i class="fas fa-random"></i></button>
                        <button class="prev" aria-label="Previous Song"><i class="fas fa-step-forward"></i></button>
                        <button class="rewind" aria-label="Rewind 15 Seconds">
                            <span class="icon-wrapper">
                                <i class="fas fa-redo"></i>
                                <span class="number-overlay">15</span>
                            </span>
                        </button>
                        <button class="play-pause" aria-label="Play/Pause"><i class="fas fa-play"></i></button>
                        <button class="fast-forward" aria-label="Fast Forward 15 Seconds">
                            <span class="icon-wrapper">
                                <i class="fas fa-undo"></i>
                                <span class="number-overlay">15</span>
                            </span>
                        </button>
                        <button class="next" aria-label="Next Song"><i class="fas fa-step-backward"></i></button>
                        <button class="repeat" title="Repeat"><i class="fas fa-redo"></i></button>
                        <!-- دکمه جدید برای تغییر وضعیت پلی‌لیست -->
                        <button class="playlist-toggle" aria-label="Toggle Playlist" title="Toggle Playlist">
                            <i class="fas fa-list-ul"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php elseif ($is_archive) : ?>
        <div class="playlist-archive">
            <?php
            $playlists = new WP_Query(['post_type' => 'playlist', 'posts_per_page' => -1]);
            global $wpdb;
            $table_name = $wpdb->prefix . 'playlist_songs';
            while ($playlists->have_posts()) : $playlists->the_post();
                $song_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE playlist_id = %d",
                    get_the_ID()
                ));
                $thumbnail = has_post_thumbnail() 
                    ? get_the_post_thumbnail_url(null, 'medium') 
                    : plugin_dir_url(dirname(__FILE__)) . 'assets/image/default-playlist.jpg';
        ?>
            <a href="<?php the_permalink(); ?>" class="playlist-card">
                <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr(get_the_title()); ?>"
                    class="playlist-cover">
                <h2 class="playlist-title"><?php echo esc_html(get_the_title()); ?></h2>
                <span class="song-count"><?php echo esc_html($song_count); ?> Songs</span>
            </a>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
        <?php endif; ?>
    </div>