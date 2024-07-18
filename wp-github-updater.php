<?php
/*
Plugin Name: WP GitHub Updater
Plugin URI: https://github.com/Lkilasonia/wp-github-updater
Description: A plugin that updates itself from GitHub releases.
Version: 1.0.0
Author: Your Name
Author URI: https://github.com/Lkilasonia
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Include the Composer autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
}

define('WP_GITHUB_UPDATER_VERSION', '1.0.0');

use WPGitHubUpdater\Example;
use WPGitHubUpdater\Updater;

add_action('init', function() {
    echo Example::greet();
});

add_filter('site_transient_update_plugins', [Updater::class, 'check_for_updates']);
add_action('upgrader_process_complete', [Updater::class, 'upgrader_process_complete'], 10, 2);
