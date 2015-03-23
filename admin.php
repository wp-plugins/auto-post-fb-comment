<?php
// create custom plugin settings menu
add_action('admin_menu', 'apfc_create_menu');

function apfc_create_menu() {

    //create new top-level menu
    add_menu_page('Auto Post FB Comment Settings', 'Auto Post FB Comment', 'administrator', __FILE__, 'apfc_settings_page', plugins_url('/images/icon.png', __FILE__));

    //call register settings function
    add_action('admin_init', 'register_mysettings');
}

function register_mysettings() {
    //register our settings
    register_setting('apfc-settings-group', 'apfc_app_id');
    register_setting('apfc-settings-group', 'apfc_approved');
    register_setting('apfc-settings-group', 'apfc_debug');
    register_setting('apfc-settings-group', 'apfc_width');
    register_setting('apfc-settings-group', 'apfc_numposts');
    register_setting('apfc-settings-group', 'apfc_colorscheme');
}

function apfc_settings_page() {
    ?>
    <div class="wrap">
        <h2>Auto Post FB Comment Settings</h2>

        <form method="post" action="options.php">
            <?php settings_fields('apfc-settings-group'); ?>
            <?php do_settings_sections('apfc-settings-group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">App ID</th>
                    <td>
                        <input type="text" name="apfc_app_id" value="<?php echo esc_attr(get_option('apfc_app_id')); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Default comment status</th>
                    <td>
                        <select name="apfc_approved">
                            <option value="1"<?php echo (esc_attr(get_option('apfc_approved')) == 1) ? " selected='selected'" : "" ?>>Approved</option>
                            <option value="0"<?php echo (esc_attr(get_option('apfc_approved')) == 0) ? " selected='selected'" : "" ?>>Pending</option>
                            <option value="spam"<?php echo (esc_attr(get_option('apfc_approved')) == "spam") ? " selected='selected'" : "" ?>>Spam</option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Width</th>
                    <td>
                        <input type="text" name="apfc_width" value="<?php echo esc_attr(get_option('apfc_width', 550)); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Number of Posts</th>
                    <td>
                        <input type="text" name="apfc_numposts" value="<?php echo esc_attr(get_option('apfc_numposts', 5)); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Color Scheme</th>
                    <td>
                        <select name="apfc_colorscheme">
                            <option value="light"<?php echo (esc_attr(get_option('apfc_colorscheme')) == "light") ? " selected='selected'" : "" ?>>Light</option>
                            <option value="dark"<?php echo (esc_attr(get_option('apfc_colorscheme')) == "dark") ? " selected='selected'" : "" ?>>Dark</option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Turn on debug?</th>
                    <td>
                        <select name="apfc_debug">
                            <option value="0"<?php echo (esc_attr(get_option('apfc_debug')) == 0) ? " selected='selected'" : "" ?>>False</option>
                            <option value="1"<?php echo (esc_attr(get_option('apfc_debug')) == 1) ? " selected='selected'" : "" ?>>True</option>
                        </select>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>

        </form>
    </div>
<?php } ?>