<?php

namespace WPREPAIR;

class Backup
{
    public static function first_backup()
    {
        //start with determining if it is (l)unix or windows
        if (empty(get_option('wpr_os'))) {
            ExecCommands::os();
            $os = get_option('wpr_os');
            if ($os == "windows") {
                General::log_message("Plugin does not yet work on a windows operating system.");
                wp_redirect(admin_url() . 'tools.php?page=wp-repair&notice=windows_error');
                exit;
            }
        }

        //create first backups
        $wpr_plugins_dir = preg_replace("/includes\//","",plugin_dir_path( __FILE__ ));
        $base_dir = trailingslashit(ABSPATH) . 'wp-repair';
        $result = wp_mkdir_p($base_dir);
        if ($result === false) {
            General::log_message("Plugin won\'t work because plugin is not able to create backup directory.");
            wp_redirect(admin_url() . 'tools.php?page=wp-repair&notice=dir_failure');
            exit;
        } else {
            //create a htaccess file denying access from anyone outside
            WPRaccess::protect_backups($base_dir . '/.htaccess');

            //create db_config.php file with access to database
            WPRaccess::db_config($base_dir . '/db_config.php');

            //copy the wp-repair-index.php to wp-repair directory
            $src = $wpr_plugins_dir . 'wp-repair-index.php';
            $dst = $base_dir . '/index.php';
            ExecCommands::command($src, $dst, 'index');

            //copy the wp-repair-zip.php to wp-repair directory
            $src = $wpr_plugins_dir . 'wp-repair-zip.php';
            $dst = $base_dir . '/zip.php';
            ExecCommands::command($src, $dst, 'copy-zip-file');

            //copy vendor there to use GuzzleHttp
            $src = $wpr_plugins_dir . 'vendor';
            $dst = $base_dir;
            ExecCommands::command($src, $dst, 'dir');

            //install wp-cli there
            $src = '';
            $dst = $base_dir;
            ExecCommands::command($src, $dst, 'wp-cli');

            //and save config path
            ExecCommands::command("", "", 'config-path');

            //just in case daily_backup gets called twice first check if it already exists
            $dst = $base_dir . '/' . date("M-j");
            if (!is_dir($dst)) {
                $code = get_option( 'wpr_code' );
                $token = get_option( 'wpr_token' );
                fopen(get_site_url() . '/wp-repair/index.php?action=start-backup&code='.$code.'&token='.$token, "r");
            }

            return "OK";
        }
    }

    public function delete_all()
    {
        $code = get_option( 'wpr_code' );
        $token = get_option( 'wpr_token' );

        fopen(get_site_url() . '/wp-repair/index.php?action=delete-all&code='.$code.'&token='.$token, "r");

        wp_redirect(admin_url() . 'tools.php?page=wp-repair&notice=delete_success');
        exit;
    }
}