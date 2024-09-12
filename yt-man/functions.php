<?php
/**
 * The YouTube Man v1
 *
 *
 * written by: Hasan AlDoy
 */

/**
 * Wordpress Notification
 */
function cwpai_create_wordpress_notification( $message ) {
    $title = sanitize_text_field( $message['title'] );
    $content = sanitize_textarea_field( $message['content'] );

    $notification = array(
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => 'publish',
        'post_type'    => 'notification',
    );

    wp_insert_post( $notification );
}


// Hook into the 'wp_dashboard_setup' action to register our widget
add_action('wp_dashboard_setup', 'cwpai_youtube_playlist_widget');

function cwpai_youtube_playlist_widget()
{
    // Check if current user is an admin
    if (current_user_can('manage_options')) {
        wp_add_dashboard_widget(
            'cwpai_youtube_playlists_widget',    // Widget slug
            'YouTube Playlists',                 // Widget title
            'cwpai_display_youtube_playlists'    // Callback function
        );
    }
}

// The callback function to display widget content
function cwpai_display_youtube_playlists()
{
    $channel_id = 'YOUR_CHANNEL_ID_HERE';
    $api_key = 'YOUR_API_KEY_HERE';

    // Get playlists from YouTube API
    $playlists_url = "https://www.googleapis.com/youtube/v3/playlists?part=snippet&channelId={$channel_id}&key={$api_key}";
    $playlists_response = wp_remote_get($playlists_url);
    $playlists_body = wp_remote_retrieve_body($playlists_response);
    $playlists_data = json_decode($playlists_body, true);

    if (!empty($playlists_data['items'])) {
        echo '<ul>';
        foreach ($playlists_data['items'] as $playlist) {
            $playlist_id = $playlist['id'];
            $playlist_title = $playlist['snippet']['title'];
            echo "<li><a href='#' data-playlist-id='{$playlist_id}'>{$playlist_title}</a></li>";
        }
        echo '</ul>';

        echo '<div id="cwpai-playlist-videos"></div>';

        // Enqueue script for AJAX
        wp_enqueue_script('cwpai-youtube-widget', plugin_dir_url(__FILE__) . 'youtube-widget.js', array('jquery'), '1.0.0', true);
        wp_localize_script('cwpai-youtube-widget', 'cwpaiYouTubeWidget', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'apiKey' => $api_key
        ));

    } else {
        echo 'No playlists found.';
    }
}

// AJAX handler to fetch playlist videos
add_action('wp_ajax_cwpai_get_playlist_videos', 'cwpai_get_playlist_videos');

function cwpai_get_playlist_videos()
{
    $playlist_id = $_POST['playlistId'];
    $api_key = $_POST['apiKey'];

    // Get videos from playlist using YouTube API
    $playlist_url = "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&playlistId={$playlist_id}&key={$api_key}";
    $playlist_response = wp_remote_get($playlist_url);
    $playlist_body = wp_remote_retrieve_body($playlist_response);
    $playlist_data = json_decode($playlist_body, true);

    if (!empty($playlist_data['items'])) {
        echo '<ul>';
        foreach ($playlist_data['items'] as $video) {
            $video_title = $video['snippet']['title'];
            $video_id = $video['snippet']['resourceId']['videoId'];
            $video_url = "https://www.youtube.com/watch?v={$video_id}";
            echo "<li><a href='{$video_url}' target='_blank'>{$video_title}</a></li>";
        }
        echo '</ul>';
    } else {
        echo 'No videos found in this playlist.';
    }

    wp_die(); // Required for AJAX in WP
}
