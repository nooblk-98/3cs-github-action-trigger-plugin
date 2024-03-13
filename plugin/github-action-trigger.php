<?php
/**
 * Plugin Name: GitHub Action Trigger
 * Description: A plugin to trigger a GitHub Action from the WordPress dashboard and view the workflow status with automatic updates.
 * Version: 2.3
 * Author: lahiru@3cs.solutions
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
        $workflow_file = get_option('gat_workflow_file');
        $branch_name = get_option('gat_branch_name');

        $response = wp_remote_post(
            "https://api.github.com/repos/$github_repo/actions/workflows/$workflow_file/dispatches",
            array(
                'method'    => 'POST',
                'headers'   => array(
                    'Accept'        => 'application/vnd.github.v3+json',
                    'Authorization' => "token $github_token",
                    'Content-Type'  => 'application/json'
                ),
                'body'      => json_encode(array(
                    'ref' => $branch_name,
                )),
                'timeout'   => 60
            )
        );

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            echo "<p>Failed to trigger action: $error_message</p>";
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code == 204) {
                echo '<p>OK! GitHub Action triggered successfully.</p>';
            } else {
                $body = wp_remote_retrieve_body($response);
                echo "<p>Failed to trigger action: $body</p>";
            }
        }
    }

    // Display the trigger button
    echo '<form method="post">';
    echo '<input type="submit" name="trigger_action" value="Deploy Site Changes to Live">';
    echo '</form>';

    // Container for GitHub workflow status
    echo '<div id="github-workflow-status"></div>';

    // Script for periodic refresh
    echo '<script>
            function fetchWorkflowStatus() {
                var xhr = new XMLHttpRequest();
                xhr.open("GET", "' . admin_url('admin-ajax.php') . '?action=gat_fetch_workflow_status", true);
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        document.getElementById("github-workflow-status").innerHTML = xhr.responseText;
                    }
                };
                xhr.send();
            }
            fetchWorkflowStatus();
            setInterval(fetchWorkflowStatus, 1000);
          </script>';
}


// Function to display the GitHub workflow status
function gat_display_workflow_status() {
    echo '<h2>GitHub Workflow Status</h2>';

    $github_token = get_option('gat_github_token');
    $github_repo = get_option('gat_github_repo');
    $workflow_runs = gws_fetch_workflow_runs($github_token, $github_repo);

    if (!empty($workflow_runs)) {
        echo '<ul style="list-style: none;">';
        foreach ($workflow_runs as $run) {
            $status_emoji = $run->status == 'completed' ? '✅' : '⏳';
            $conclusion_emoji = $run->conclusion == 'success' ? '✅' : ($run->conclusion == 'failure' ? '❌' : '❓');
            $log_url = "https://github.com/$github_repo/actions/runs/$run->id";
            $start_time = date('Y-m-d H:i:s', strtotime($run->created_at)); // Format the start time
            echo '<li>';
            echo '<p><strong>Run ID:</strong> <a href="' . $log_url . '" target="_blank">' . $run->id . '</a></p>';
            echo '<p><strong>Status:</strong> ' . $status_emoji . ' ' . $run->status . '</p>';
            echo '<p><strong>Conclusion:</strong> ' . $conclusion_emoji . ' ' . ($run->conclusion ?? 'N/A') . '</p>';
            echo '<p><strong>Started At:</strong> ' . $start_time . '</p>';
            echo '</li>';
            echo '<hr>';
        }
        echo '</ul>';
    } else {
        echo '<p>No workflow runs found.</p>';
    }
}


// Function to fetch workflow runs from GitHub API
function gws_fetch_workflow_runs($github_token, $github_repo) {
    $url = "https://api.github.com/repos/$github_repo/actions/runs";

    $args = array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $github_token,
            'Accept'        => 'application/vnd.github.v3+json',
            'User-Agent'    => 'WordPress-GitHub-Action-Trigger'
        )
    );

    $response = wp_remote_get($url, $args);

    if (is_wp_error($response)) {
        return array();
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);

    return $data->workflow_runs ?? array();
}

// AJAX action for fetching GitHub workflow status
add_action('wp_ajax_gat_fetch_workflow_status', 'gat_fetch_workflow_status_ajax');
function gat_fetch_workflow_status_ajax() {
    gat_display_workflow_status();
    wp_die(); // This is required to terminate immediately and return a proper response
}
