<?php
// Register settings
add_action('admin_init', 'gat_register_settings');
function gat_register_settings() {
    add_option('gat_github_token', '');
    add_option('gat_github_repo', '');
    add_option('gat_workflow_file', ''); // Add this line
    add_option('gat_branch_name', ''); // Add this line
    register_setting('gat_options_group', 'gat_github_token', 'gat_callback');
    register_setting('gat_options_group', 'gat_github_repo', 'gat_callback');
    register_setting('gat_options_group', 'gat_workflow_file', 'gat_callback'); // Add this line
    register_setting('gat_options_group', 'gat_branch_name', 'gat_callback'); // Add this line
}

// Settings page
function gat_settings_page() {
    ?>
    <div class="wrap">
        <h1>GitHub Action Trigger Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('gat_options_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">GitHub Token</th>
                    <td><input type="password" name="gat_github_token" value="<?php echo get_option('gat_github_token'); ?>" autocomplete="off" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">GitHub Repository Name</th>
                    <td><input type="text" name="gat_github_repo" value="<?php echo get_option('gat_github_repo'); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">GitHub Branch Name</th>
                    <td><input type="text" name="gat_branch_name" value="<?php echo get_option('gat_branch_name'); ?>" /></td>
                </tr>
                <tr valign="top"> <!-- Add this block -->
                    <th scope="row">Workflow File Name</th>
                    <td><input type="text" name="gat_workflow_file" value="<?php echo get_option('gat_workflow_file'); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
