<?php
/*
 *
Plugin Name: Maintenance IP Manager (Auto MU)
Plugin URI: https://bideogemu.com/maintenance-ip-manager
Description: Manage IPs excluded from Maintenance plugin with automatic MU-plugin deployment.
Version: 1
Author: BideoGemu
Author URI: https://bideogemu.com
License: GPL2
*/

add_action('admin_menu', function () {
    add_options_page('Maintenance IPs', 'Maintenance IPs', 'manage_options', 'maintenance-ip-manager', 'mip_render_settings_page');
});

register_activation_hook(__FILE__, 'mip_install_mu_plugin');
register_deactivation_hook(__FILE__, 'mip_deactivate_cleanup');

function mip_render_settings_page() {
    echo '<div class="wrap">';
    echo '<h1>Allowed IPs – Maintenance</h1>';
    echo '<p><strong>Enter one IP address per line. Only valid IP addresses will be saved.</strong></p>';

    if (isset($_POST['allowed_ips'])) {
        $lines = explode("\n", $_POST['allowed_ips']);
        $ips = array();
        foreach ($lines as $line) {
            $ip = trim($line);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                $ips[] = $ip;
            }
        }
        update_option('allowed_maintenance_ips', $ips);
        echo '<div class="updated"><p>IPs saved successfully.</p></div>';
    }

    $saved_ips = get_option('allowed_maintenance_ips', []);
    echo '<form method="post">';
    echo '<textarea name="allowed_ips" rows="10" cols="50" class="large-text code">' . esc_textarea(implode("\n", $saved_ips)) . '</textarea>';
    echo '<p class="submit"><button type="submit" class="button-primary">Save IPs</button></p>';
    echo '</form>';

    echo '<hr>';

    echo '<form method="post">';
    echo '<input type="hidden" name="regenerate_mu_plugin" value="1">';
    echo '<p class="submit"><button type="submit" class="button-secondary">Regenerate MU Plugin</button></p>';
    echo '</form>';
    echo '</div>';
}

function mip_install_mu_plugin() {
    $mu_dir = WP_CONTENT_DIR . '/mu-plugins';
    $mu_file = $mu_dir . '/mu-disable-maintenance-by-ip.php';

    if (!file_exists($mu_dir)) {
        mkdir($mu_dir, 0755, true);
    }

    $mu_code = <<<PHP
<?php
/*
Plugin Name: Disable Maintenance for Allowed IPs (MU Plugin)
Description: Disables the Maintenance plugin for allowed IPs.
*/

// Skip in admin area
if (is_admin()) return;

\$remote_ip = \$_SERVER['REMOTE_ADDR'] ?? '';
\$allowed_ips = get_option('allowed_maintenance_ips', []);
if (!is_array(\$allowed_ips)) {
    \$allowed_ips = [];
}

if (in_array(\$remote_ip, \$allowed_ips, true)) {
    add_filter('option_active_plugins', function (\$plugins) {
        return array_filter(\$plugins, function (\$plugin) {
            return strpos(\$plugin, 'maintenance') === false;
        });
    });

    add_filter('site_option_active_sitewide_plugins', function (\$plugins) {
        foreach (\$plugins as \$plugin => \$data) {
            if (strpos(\$plugin, 'maintenance') !== false) {
                unset(\$plugins[\$plugin]);
            }
        }
        return \$plugins;
    });
}
PHP;

    file_put_contents($mu_file, $mu_code);
}

function mip_deactivate_cleanup() {
    $mu_file = WP_CONTENT_DIR . '/mu-plugins/mu-disable-maintenance-by-ip.php';
    if (file_exists($mu_file)) {
        @unlink($mu_file);
    }
}