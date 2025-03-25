<!-- playlist-template.php -->
<div class="music-player">
    <div class="player-controls">
        <button class="prev" aria-label="Previous Song"><i class="fas fa-forward"></i></button>
        <button class="play-pause" aria-label="Play or Pause"><i class="fas fa-play"></i></button>
        <button class="next" aria-label="Next Song"><i class="fas fa-backward"></i></button>
        <button class="shuffle" aria-label="Shuffle Playlist"><i class="fas fa-random"></i></button>
        <div class="range-container">
            <div class="volume">
                <input type="range" class="volume-slider" min="0" max="1" step="0.01" value="1"
                    aria-label="Volume Control">
                <span class="volume-display">100</span>
            </div>
            <div class="time">
                <span class="current-time" aria-label="Current Time">00:00</span>
                <input type="range" class="seekbar" value="0" aria-label="Seek Bar" style="--value: 0%;">
                <span class="duration-time" aria-label="Duration">00:00</span>
            </div>
        </div>
    </div>
    <ul class="playlist" role="listbox">
        <?php foreach ($songs as $index => $song) : ?>
        <li class="playlist_item" data-src="<?php echo esc_url($song['url']); ?>" role="option" tabindex="0">
            <span class="song-title"><?php echo esc_html($song['title']); ?> -
                <?php echo esc_html($song['artist']); ?></span>
            <a href="<?php echo esc_url($song['url']); ?>" class="download-song" download
                aria-label="Download <?php echo esc_attr($song['title']); ?>"><i class="fas fa-download"></i></a>
        </li>
        <?php endforeach; ?>
    </ul>
</div>