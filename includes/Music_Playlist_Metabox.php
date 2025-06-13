<?php
namespace TuneTales_Music;

class Music_Playlist_Metabox {
    public function playlist_meta_box() {
        add_meta_box('playlist_songs', __('Playlist Songs', 'music-playlist'), [$this, 'render_meta_box'], 'playlist');
    }

    public function render_meta_box($post) {
        wp_nonce_field('save_playlist_songs', 'playlist_songs_nonce');
        $songs = get_post_meta($post->ID, '_playlist_songs', true) ?: [];
        $all_songs_id = $this->get_all_songs_post_id();
        $playlists = get_posts([
            'post_type' => 'playlist',
            'numberposts' => -1,
            'post_status' => 'publish',
            'post__not_in' => [$all_songs_id],
        ]);
        ?>
<div id="playlist_songs_wrapper">
    <?php foreach ($songs as $index => $song) : if (is_array($song)) : ?>
    <div class="playlist_song_item">
        <div class="song-url-wrapper">
            <input type="text" name="playlist_songs[url][]" value="<?php echo esc_attr($song['url']); ?>"
                class="playlist_song_input" readonly />
        </div>
        <div class="playlist-actions">
            <div class="playlist-checkboxes">
                <p><?php _e('Select Playlists:', 'music-playlist'); ?></p>
                <div class="checkbox-list">
                    <?php foreach ($playlists as $playlist) : ?>
                    <label class="checkbox-item">
                        <input type="checkbox" name="playlist_songs[playlists][<?php echo $index; ?>][]"
                            value="<?php echo $playlist->ID; ?>"
                            <?php echo in_array($playlist->ID, $song['playlists'] ?? []) ? 'checked' : ''; ?> />
                        <?php echo esc_html($playlist->post_title); ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="new-playlist-wrapper">
                <input type="text" class="new_playlist_input"
                    placeholder="<?php _e('New Playlist', 'music-playlist'); ?>" />
                <button type="button" class="button add_new_playlist_button">
                    <span class="dashicons dashicons-plus-alt"></span> <?php _e('Add', 'music-playlist'); ?>
                </button>
            </div>
            <button type="button" class="button remove_song_button">
                <span class="dashicons dashicons-trash"></span> <?php _e('Remove', 'music-playlist'); ?>
            </button>
        </div>
    </div>
    <?php endif; endforeach; ?>
</div>
<p><button type="button" id="add_multiple_songs_button">
        <?php _e('Add Multiple Songs', 'music-playlist'); ?>
    </button></p>
<?php
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