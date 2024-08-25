<!-- playlist-template.php -->
<div class="music-player">
    <div class="player-controls">
        <button class="prev"><i class="fas fa-forward"></i></button>
        <button class="play-pause"><i class="fas fa-play"></i></button>
        <button class="next"><i class="fas fa-backward"></i></button>
        <button class="shuffle"><i class="fas fa-random"></i></button>
        <div class="range-container">
            <div class="volume">
                <input type="range" class="volume-slider" min="0" max="1" step="0.01" value="1">
                <span class="volume-display">100</span>
            </div>

            <div class="time"><span class="current-time">00:00</span>
                <input type="range" class="seekbar" value="0">
                <span class="duration-time">00:00</span>
            </div>
        </div>
    </div>
    <ul class="playlist">
        <?php foreach ($songs as $song): ?>
        <li class="playlist_item" data-src="<?php echo esc_url($song['url']); ?>">
            <span class="song-title"><?php echo esc_html($song['title']); ?> -
                <?php echo esc_html($song['artist']); ?></span>
            <a href="<?php echo esc_url($song['url']); ?>" class="download-song" download><i
                    class="fas fa-download"></i></a>
        </li>
        <?php endforeach; ?>
    </ul>
</div>