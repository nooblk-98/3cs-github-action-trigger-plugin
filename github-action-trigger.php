<?php
/**
 * Plugin Name: GitHub Action Trigger
 * Description: A plugin to trigger a GitHub Action from the WordPress dashboard.
 * Version: 2.0
 * Author: liynage@3cs.solutions
 */

// Include the settings page
require_once plugin_dir_path(__FILE__) . 'settings-page.php';

// Add a menu item to the WordPress dashboard
add_action('admin_menu', 'gat_add_menu_page');
function gat_add_menu_page() {
    add_menu_page(
        'GitHub Action Trigger',
        '3CS Deployment',
        'manage_options',
        'github-action-trigger',
        'gat_trigger_action_page'
    );
    add_submenu_page(
        'github-action-trigger',
        'Settings',
        'Settings',
        'manage_options',
        'gat-settings',
        'gat_settings_page'
    );
}

// The function to display the trigger button and handle the request
function gat_trigger_action_page() {
    echo '<h1>Deploy to Live</h1>';
    
    // Check if the trigger button was pressed
    if (isset($_POST['trigger_action'])) {
        $github_token = get_option('gat_github_token');
        $github_repo = get_option('gat_github_repo');
        $response = wp_remote_post(
            "https://api.github.com/repos/3CSDesign/$github_repo/dispatches",
            array(
                'method'    => 'POST',
                'headers'   => array(
                    'Accept'        => 'application/vnd.github.v3+json',
                    'Authorization' => "token $github_token",
                    'Content-Type'  => 'application/json'
                ),
                'body'      => json_encode(array('event_type' => 'wordpress-update')),
                'timeout'   => 60
            )
        );

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            echo "<p>Failed to trigger action: $error_message</p>";
        } else {
            echo '<p>GitHub Action triggered successfully!</p>';
        }
    }

    // Display the trigger button
    echo '<form method="post">';
    echo '<input type="submit" name="trigger_action" value="Deploy Site Changes to Live">';
    echo '</form>';
}
