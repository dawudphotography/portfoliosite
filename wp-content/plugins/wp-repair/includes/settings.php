<?php

namespace WPREPAIR;

class Settings
{
    public static $settings_slug = 'wp-repair';

    public static function settings_init()
    {
        add_settings_section(
            'settings_section',
            __(''),
            [Settings::class, 'settings_text'],
            Settings::$settings_slug
        );
    }

    public static function settings_text($args)
    {
    }

    public static function settings_menu()
    {
        add_management_page(
            __('WP Repair'),
            __('WP Repair'),
            'administrator',
            Settings::$settings_slug,
            [Settings::class, 'settings_page']
        );
    }

    public static function settings_page()
    {
        $style = plugin_dir_url( dirname( __FILE__ ) ).'templates/style.css';
        wp_enqueue_style( 'stylesheet', $style );
        $script = plugin_dir_url( dirname( __FILE__ ) ).'templates/script.js';
        wp_enqueue_script( 'script-name', $script );

        $wpr_plugins_dir = preg_replace("/includes\//","",plugin_dir_path( __FILE__ ));

        include $wpr_plugins_dir.'templates/settings.php';
    }

    //(UN)ACCEPT + EXCHANGE
    public static function access()
    {
        $backups_enabled_value = get_option( 'wpr_accept_terms' );
        if ($backups_enabled_value=="on") self::remove_accept();
        else {
            //check php version, must be at least 5.4.0 because of possible safe_mode on configuration
            if (strnatcmp(phpversion(),'5.4.0') < 0)
            {
                General::log_message("Plugin won\'t work because you have php version ".phpversion().". The php version must be at least 5.4.0.");
                wp_redirect(admin_url() . 'tools.php?page=wp-repair&notice=phpversion_failure');
                exit;
            }
            //check if exec is enabled
            if(!function_exists('exec')) {
                General::log_message("Plugin won\'t work because the function exec isn\'t enabled.");
                wp_redirect(admin_url() . 'tools.php?page=wp-repair&notice=exec_failure');
                exit;
            }
            //make sure wordpress cron has not been disabled
            if ((defined( 'DISABLE_WP_CRON' )) and (DISABLE_WP_CRON==true)) {
                General::log_message("Plugin won\'t work because wp cron has been disabled.");
                wp_redirect(admin_url() . 'tools.php?page=wp-repair&notice=cron_failure');
                exit;
            }
            //check if htaccess exists and is writable and add line, if not then don't continue
            $htaccess = ABSPATH . '.htaccess';
            $result = WPRaccess::add_access($htaccess);
            if (($result==false) || ($result==-1)) {
                General::log_message("Plugin won\'t work because plugin is not able to edit the .htaccess file in the public directory.");
                wp_redirect(admin_url() . 'tools.php?page=wp-repair&notice=htaccess_failure');
                exit;
            }
            $result = self::add_accept();
            if ($result == "OK") {
                update_option('wpr_accept_terms', sanitize_text_field($_POST['backups_enabled']));
                $result = Backup::first_backup();
                if ($result=="OK") {
                    //send e-mail with further instructions
                    General::send_email('backups_enabled');
                    General::log_message("First backup in process.");
                    wp_redirect(admin_url() . 'tools.php?page=wp-repair&notice=enabled_backups');
                    exit;
                }
                else {
                    General::log_message("Backup not completed because of low disk space. Ask your hoster for more disk space.");
                    wp_redirect(admin_url() . 'tools.php?page=wp-repair&notice=enabled_backups_backup_error');
                    exit;
                }
            }
            else {
                General::log_message("Plugin won\'t work because plugin failed to exchange a token with https://wp.repair.");
                wp_redirect(admin_url() . 'tools.php?page=wp-repair&notice=token_failure');
                exit;
            }
        }
    }

    public static function access_ref()
    {
        $backups_enabled_ref_value = get_option( 'wpr_accept_terms_ref' );
        if ($backups_enabled_ref_value=="on") {
            delete_option('wpr_accept_terms_ref');
            $wpr_postid = get_option( 'wpr_postid' );
            wp_delete_post($wpr_postid, true);
            delete_option('wpr_postid');
            wp_redirect(admin_url() . 'tools.php?page=wp-repair');
            exit;
        }
        else {
            update_option('wpr_accept_terms_ref', sanitize_text_field($_POST['backups_enabled_ref']));
            General::post_blog();
            wp_redirect(admin_url() . 'tools.php?page=wp-repair');
            exit;
        }
    }

    public static function add_accept()
    {
        //connect with wp.repair and request token
        $domain = get_site_url();
        $code = General::randomCode();
        update_option('wpr_code', $code);
        delete_option('wpr_token');
        General::wpr_flush();
        $result = General::api_connect($domain, $code, 'token');

        return($result);
    }

    public static function remove_accept()
    {
        delete_option('wpr_accept_terms');
        update_option('wpr_verified', "0");

        $domain = get_site_url();
        $code = get_option( 'wpr_code' );

        General::api_connect($domain, $code, 'drop');

        $htaccess = ABSPATH . '.htaccess';
        if (is_writable($htaccess)) {
            WPRaccess::remove_access($htaccess);
        }

        General::log_message("Backup\'s disabled.");
        wp_redirect(admin_url() . 'tools.php?page=wp-repair&notice=dropped_user');
        exit;
    }

    public static function deactivate()
    {
        delete_option('wpr_accept_terms');
        update_option('wpr_verified', "0");

        $domain = get_site_url();
        $code = get_option( 'wpr_code' );

        General::api_connect($domain, $code, 'drop');

        $htaccess = ABSPATH . '.htaccess';
        if (is_writable($htaccess)) {
            WPRaccess::remove_access($htaccess);
        }

        General::log_message("Plugin deactivated.");
    }

    public static function uncheck_accept()
    {
        delete_option('wpr_accept_settings');
        update_option('wpr_verified', "0");

        $htaccess = ABSPATH . '.htaccess';
        if (is_writable($htaccess)) {
            WPRaccess::remove_access($htaccess);
        }

        General::log_message("Backup\'s disabled, because your account has expired.");
    }
}