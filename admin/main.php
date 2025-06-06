<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get playlists
$playlists = get_posts([
    'post_type' => 'playlist',
    'numberposts' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
]);

// Get selected playlist ID
$selected_playlist_id = isset($_GET['playlist_id']) ? intval($_GET['playlist_id']) : 0;

// Get tracks if playlist is selected
$tracks = [];
echo "selected_playlist_id:".$selected_playlist_id;

if ($selected_playlist_id) {
    $tracks = get_post_meta($selected_playlist_id, '_playlist_songs', true);
    echo print_r($tracks,true);
}
?>

<div class="wrap">
    <h1><?php echo esc_html__('Music Playlist Manager', 'music-playlist'); ?></h1>

    <div class="music-playlist-admin-container">
        <div class="music-playlist-admin-sidebar">
            <div class="music-playlist-admin-box">
                <h2><?php echo esc_html__('Playlists', 'music-playlist'); ?></h2>

                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=music_playlist')); ?>"
                    class="button button-primary">
                    <?php echo esc_html__('Add New Playlist', 'music-playlist'); ?>
                </a>

                <div class="music-playlist-list">
                    <?php if (empty($playlists)) : ?>
                    <p><?php echo esc_html__('No playlists found. Create your first playlist to get started.', 'music-playlist'); ?>
                    </p>
                    <?php else : ?>
                    <ul>
                        <?php foreach ($playlists as $playlist) : ?>
                        <li class="<?php echo $selected_playlist_id === $playlist->ID ? 'active' : ''; ?>">
                            <a
                                href="<?php echo esc_url(admin_url('admin.php?page=music-playlist&playlist_id=' . $playlist->ID)); ?>">
                                <?php echo esc_html($playlist->post_title); ?>
                            </a>
                            <div class="playlist-actions">
                                <a href="<?php echo esc_url(get_edit_post_link($playlist->ID)); ?>"
                                    title="<?php echo esc_attr__('Edit', 'music-playlist'); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </a>
                                <a href="<?php echo esc_url(get_delete_post_link($playlist->ID)); ?>"
                                    title="<?php echo esc_attr__('Delete', 'music-playlist'); ?>"
                                    class="delete-playlist">
                                    <span class="dashicons dashicons-trash"></span>
                                </a>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>

                <div class="music-playlist-shortcode-info">
                    <h3><?php echo esc_html__('Shortcode', 'music-playlist'); ?></h3>
                    <?php if ($selected_playlist_id) : ?>
                    <code>[music_playlist id="<?php echo esc_attr($selected_playlist_id); ?>"]</code>
                    <button class="button copy-shortcode"
                        data-shortcode='[music_playlist id="<?php echo esc_attr($selected_playlist_id); ?>"]'>
                        <?php echo esc_html__('Copy', 'music-playlist'); ?>
                    </button>
                    <?php else : ?>
                    <p><?php echo esc_html__('Select a playlist to see its shortcode.', 'music-playlist'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="music-playlist-admin-content">
            <?php if ($selected_playlist_id) : ?>
            <?php $playlist = get_post($selected_playlist_id); ?>

            <div class="music-playlist-admin-box">
                <h2><?php echo esc_html(sprintf(__('Manage Tracks: %s', 'music-playlist'), $playlist->post_title)); ?>
                </h2>

                <div class="music-playlist-add-track">
                    <h3><?php echo esc_html__('Add New Track', 'music-playlist'); ?></h3>

                    <form id="music-playlist-add-track-form">
                        <input type="hidden" name="playlist_id" value="<?php echo esc_attr($selected_playlist_id); ?>">

                        <div class="form-row">
                            <div class="form-field">
                                <label for="track-title"><?php echo esc_html__('Title', 'music-playlist'); ?> *</label>
                                <input type="text" id="track-title" name="title" required>
                            </div>

                            <div class="form-field">
                                <label for="track-artist"><?php echo esc_html__('Artist', 'music-playlist'); ?></label>
                                <input type="text" id="track-artist" name="artist">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-field">
                                <label for="track-file"><?php echo esc_html__('Audio File', 'music-playlist'); ?>
                                    *</label>
                                <input type="text" id="track-file" name="file_url" class="regular-text" required
                                    readonly>
                                <button type="button" id="select-audio-file" class="button">
                                    <?php echo esc_html__('Select File', 'music-playlist'); ?>
                                </button>
                                <p class="description">
                                    <?php echo esc_html__('Supported formats: MP3, WAV, OGG', 'music-playlist'); ?></p>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-field">
                                <label
                                    for="track-cover"><?php echo esc_html__('Cover Image', 'music-playlist'); ?></label>
                                <input type="text" id="track-cover" name="cover_image" class="regular-text" readonly>
                                <button type="button" id="select-cover-image" class="button">
                                    <?php echo esc_html__('Select Image', 'music-playlist'); ?>
                                </button>
                                <div id="cover-preview"></div>
                            </div>
                        </div>

                        <div class="form-row">
                            <button type="submit" class="button button-primary" id="add-track-btn">
                                <?php echo esc_html__('Add Track', 'music-playlist'); ?>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="music-playlist-tracks">
                    <h3><?php echo esc_html__('Playlist Tracks', 'music-playlist'); ?></h3>

                    <?php if (empty($tracks)) : ?>
                    <p><?php echo esc_html__('No tracks in this playlist yet. Add your first track above.', 'music-playlist'); ?>
                    </p>
                    <?php else : ?>
                    <table class="wp-list-table widefat fixed striped" id="music-playlist-tracks-table">
                        <thead>
                            <tr>
                                <th scope="col" class="column-order"></th>
                                <th scope="col" class="column-cover">
                                    <?php echo esc_html__('Cover', 'music-playlist'); ?></th>
                                <th scope="col" class="column-title">
                                    <?php echo esc_html__('Title', 'music-playlist'); ?></th>
                                <th scope="col" class="column-artist">
                                    <?php echo esc_html__('Artist', 'music-playlist'); ?></th>
                                <th scope="col" class="column-actions">
                                    <?php echo esc_html__('Actions', 'music-playlist'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="music-playlist-tracks-list">
                            <?php foreach ($tracks as $track) : ?>
                            <tr data-id="<?php echo esc_attr($track['id']); ?>">
                                <td class="column-order">
                                    <span class="dashicons dashicons-menu-alt3"></span>
                                </td>
                                <td class="column-cover">
                                    <?php if (!empty($track['cover_image'])) : ?>
                                    <img src="<?php echo esc_url($track['cover_image']); ?>"
                                        alt="<?php echo esc_attr($track['title']); ?>" width="40" height="40">
                                    <?php else : ?>
                                    <img src="<?php echo esc_url(MUSIC_PLAYLIST_PLUGIN_URL . 'assets/images/default-cover.png'); ?>"
                                        alt="<?php echo esc_attr($track['title']); ?>" width="40" height="40">
                                    <?php endif; ?>
                                </td>
                                <td class="column-title">
                                    <?php echo esc_html($track['title']); ?>
                                </td>
                                <td class="column-artist">
                                    <?php echo esc_html($track['artist']); ?>
                                </td>
                                <td class="column-actions">
                                    <button type="button" class="button play-track"
                                        data-file="<?php echo esc_url($track['file_url']); ?>">
                                        <span class="dashicons dashicons-controls-play"></span>
                                    </button>
                                    <button type="button" class="button delete-track"
                                        data-id="<?php echo esc_attr($track['id']); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
            <?php else : ?>
            <div class="music-playlist-admin-box">
                <h2><?php echo esc_html__('Select a Playlist', 'music-playlist'); ?></h2>
                <p><?php echo esc_html__('Please select a playlist from the sidebar or create a new one to manage tracks.', 'music-playlist'); ?>
                </p>

                <?php if (empty($playlists)) : ?>
                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=music_playlist')); ?>"
                    class="button button-primary">
                    <?php echo esc_html__('Create Your First Playlist', 'music-playlist'); ?>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="music-playlist-audio-player" class="music-playlist-audio-player">
        <audio id="admin-audio-preview" controls></audio>
    </div>
</div>