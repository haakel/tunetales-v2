<?php
$songs = isset($songs) ? $songs : [];
$is_archive = is_post_type_archive('playlist');
?>
<div class="webapp-player" id="tunetales-app">
    <header class="app-header">
        <button class="sidebar-toggle" aria-label="Toggle Playlist">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="app-title">TuneTales</h1>
        <?php if (is_singular('playlist')) : ?>
        <button class="back-to-archive" aria-label="Back to Playlist Archive">
            <i class="fas fa-arrow-left"></i> Archive
        </button>
        <?php endif; ?>
    </header>

    <?php if (is_singular('playlist') && $songs) : ?>
    <div class="player-main">
        <div class="sidebar">
            <ul class="playlist" role="listbox">
                <?php foreach ($songs as $index => $song) : 
                    $attachment_id = attachment_url_to_postid($song['url']);
                    $attachment = get_post($attachment_id);
                    $metadata = wp_get_attachment_metadata($attachment_id);
                    $title = $attachment ? $attachment->post_title : 'Unknown Title';
                    $artist = isset($metadata['artist']) ? $metadata['artist'] : 'Unknown Artist';
                    $album = isset($metadata['album']) ? $metadata['album'] : 'Unknown Album';
                    $description = $attachment ? $attachment->post_content : '';
                    $excerpt = $attachment ? $attachment->post_excerpt : '';
                ?>
                <li class="playlist_item" data-src="<?php echo esc_url($song['url']); ?>"
                    data-title="<?php echo esc_attr($title); ?>" data-artist="<?php echo esc_attr($artist); ?>"
                    data-album="<?php echo esc_attr($album); ?>"
                    data-description="<?php echo esc_attr($description); ?>"
                    data-excerpt="<?php echo esc_attr($excerpt); ?>" role="option" tabindex="0">
                    <span class="song-title"><?php echo esc_html($title); ?> - <?php echo esc_html($artist); ?></span>
                    <a href="<?php echo esc_url($song['url']); ?>" class="download-song" download
                        aria-label="Download <?php echo esc_attr($title); ?>"><i class="fas fa-download"></i></a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="main-content">
            <div class="now-playing">
                <?php
                    $current_song = $songs[0];
                    $attachment_id = attachment_url_to_postid($current_song['url']);
                    $thumbnail_id = get_post_thumbnail_id($attachment_id); // گرفتن تصویر شاخص آهنگ
                    $thumbnail = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium') : plugin_dir_url(__FILE__) . 'default-cover.jpg';
                    $attachment = get_post($attachment_id);
                    $metadata = wp_get_attachment_metadata($attachment_id);
                    $title = $attachment ? $attachment->post_title : 'Unknown Title';
                    $artist = isset($metadata['artist']) ? $metadata['artist'] : 'Unknown Artist';
                ?>
                <img src="<?php echo esc_url($thumbnail); ?>" alt="Now Playing Cover" class="cover-art">
                <div class="song-info">
                    <span class="song-title"><?php echo esc_html($title); ?></span>
                    <span class="song-artist"><?php echo esc_html($artist); ?></span>
                    <span
                        class="song-album"><?php echo esc_html(isset($metadata['album']) ? $metadata['album'] : 'Unknown Album'); ?></span>
                    <p class="song-excerpt"><?php echo esc_html($attachment ? $attachment->post_excerpt : ''); ?></p>
                    <p class="song-description"><?php echo esc_html($attachment ? $attachment->post_content : ''); ?>
                    </p>
                </div>
            </div>
            <div class="player-controls">
                <div class="range-container">
                    <div class="time">
                        <span class="current-time" aria-label="Current Time">00:00</span>
                        <input type="range" class="seekbar" value="0" aria-label="Seek Bar" style="--value: 0%;">
                        <span class="duration-time" aria-label="Duration">00:00</span>
                    </div>
                    <div class="volume">
                        <input type="range" class="volume-slider" min="0" max="1" step="0.01" value="1"
                            aria-label="Volume Control">
                        <span class="volume-display">100</span>
                    </div>
                </div>
                <div class="control-buttons">
                    <button class="shuffle" aria-label="Shuffle Playlist"><i class="fas fa-random"></i></button>
                    <button class="prev" aria-label="Previous Song"><i class="fas fa-backward"></i></button>
                    <button class="play-pause" aria-label="Play or Pause"><i class="fas fa-play"></i></button>
                    <button class="next" aria-label="Next Song"><i class="fas fa-forward"></i></button>
                    <button class="repeat" title="Repeat"><i class="fas fa-redo"></i></button>
                </div>
            </div>
        </div>
    </div>
    <?php elseif ($is_archive) : ?>
    <div class="playlist-archive">
        <?php
            $playlists = new WP_Query(['post_type' => 'playlist', 'posts_per_page' => -1]);
            while ($playlists->have_posts()) : $playlists->the_post();
                $songs = get_post_meta(get_the_ID(), '_playlist_songs', true);
                $thumbnail = has_post_thumbnail() ? get_the_post_thumbnail_url(null, 'medium') : plugin_dir_url(__FILE__) . 'default-playlist.jpg';
        ?>
        <a href="<?php the_permalink(); ?>" class="playlist-card">
            <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php the_title(); ?>" class="playlist-cover">
            <h2 class="playlist-title"><?php the_title(); ?></h2>
            <span class="song-count"><?php echo count($songs); ?> Songs</span>
        </a>
        <?php endwhile; wp_reset_postdata(); ?>
    </div>
    <?php endif; ?>
</div>