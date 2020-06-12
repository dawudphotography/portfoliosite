<?php

namespace WPREPAIR;

use GuzzleHttp\Client;

class General
{
    //EXCHANGE CODE AND TOKEN
    public static function randomCode()
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $code = array(); //remember to declare $token as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $code[] = $alphabet[$n];
        }
        return implode($code); //turn the array into a string
    }

    public static function wprepair_actions()
    {
        $page = isset($_GET['page']) ? filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING ) : null;
        $wpraction = isset($_GET['wpraction']) ? filter_input( INPUT_GET, 'wpraction', FILTER_SANITIZE_STRING ) : null;
        $notice = isset($_GET['notice']) ? filter_input( INPUT_GET, 'notice', FILTER_SANITIZE_STRING ) : null;
        $wprtoken = isset($_GET['wprtoken']) ? filter_input( INPUT_GET, 'wprtoken', FILTER_SANITIZE_STRING ) : null;
        $wprcode = isset($_GET['wprcode']) ? filter_input( INPUT_GET, 'wprcode', FILTER_SANITIZE_STRING ) : null;

        if (isset($wpraction) && ($wpraction=="wprepair") && isset($wprtoken) && isset($wprcode))  {
            $code = get_option( 'wpr_code' );

            if ($code==$wprcode) {
                //save token in wp_options
                $token = $wprtoken;
                update_option('wpr_token', $token);
                update_option('wpr_verified', "1");

                exit("OK");
            }
            else {
                exit("ERROR");
            }
        }
        elseif (isset($wpraction) && ($wpraction=="flush_all") && isset($wprtoken))  {
            $token = get_option( 'wpr_token' );

            if ($token==$wprtoken) {
                self::wpr_flush();
                exit("OK");
            }
            else {
                exit("ERROR");
            }
        }
        elseif (isset($wpraction) && ($wpraction=="remove_accept") && isset($wprtoken))  {
            $token = get_option( 'wpr_token' );

            if ($token==$wprtoken) {
                Settings::uncheck_accept();
                exit("OK");
            }
            else {
                exit("ERROR");
            }
        }
        elseif ($page=="wp-repair") {
            if (((isset($notice)) && ($notice!="guzzle_failure")) || (!isset($notice))) {
                //het current status of your account at https://wp.repair
                $domain = get_site_url();
                $result = General::api_connect($domain, "", 'status');
                update_option('wpr_status', $result);
            }
        }
    }

    public static function wpr_flush() {
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }
        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
        }
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }
        if (function_exists('apcu_clear_cache')) {
            apcu_clear_cache();
        }
        if (function_exists('opcache_reset')) {
            // Clear it twice to avoid some internal issues...
            opcache_reset();
            opcache_reset();
        }
    }
    public static function api_connect($domain, $code, $action, $postid=null)
    {
        try {
            $client = new Client();
            $options = [
                'query' => [
                    'domain' => $domain,
                    'code' => $code,
                    'action' => $action,
                    'postid' => $postid
                ]
            ];
            $response    = $client->request('GET', WPREPAIR::WPREPAIR_API_ENDPOINT, $options);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            wp_redirect(admin_url() . 'tools.php?page=wp-repair&notice=guzzle_failure');
            exit();
        }

        $result = $response->getBody()->getContents();

        return $result;
    }

    public static function log_message($message, $mId=null)
    {
        $domain = get_site_url();
        $code = get_option( 'wpr_code' );

        try {
            $client = new Client();
            $options = [
                'query' => [
                    'domain' => $domain,
                    'message' => $message,
                    'code' => $code,
                    'mId' => $mId,
                    'action' => 'log'
                ]
            ];
            $response    = $client->request('GET', WPREPAIR::WPREPAIR_API_ENDPOINT, $options);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            wp_redirect(admin_url() . 'tools.php?page=wp-repair&notice=guzzle_failure');
            exit();
        }

        $result = $response->getBody()->getContents();

        return $result;
    }

    public static function get_command($domain, $code, $shortcode, $token, $os, $dst=null)
    {
        try {
            $client = new Client();
            $options = [
                'query' => [
                    'domain' => $domain,
                    'code' => $code,
                    'shortcode' => $shortcode,
                    'token' => $token,
                    'os' => $os,
                    'dst' => $dst
                ]
            ];
            $response    = $client->request('GET', WPREPAIR::WPREPAIR_API_ENDPOINT."command.php", $options);

            $result = $response->getBody()->getContents();

            return $result;

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return "ERROR";
        }
    }

    public static function send_email($type, $subject=null, $attachment=null) {
        $current_user = wp_get_current_user();
        $uEmail =  $current_user->user_email;
        $uName = $current_user->nickname;

        $code = get_option( 'wpr_code' );
        $domain = get_site_url();
        $pieces = parse_url($domain);
        $scheme = $pieces["scheme"];
        $host = $pieces["host"];
        $newURL = $scheme.'://'. $host;

        if ($type=="backups_enabled"){
            $subject = "[Wp repair] Further instructions";
            $message = "Hi $uName,

Welcome, your first backup is in progress!
To get the most out of this plugin, create a free account at https://wp.repair/registration/ and fill in your activation code there. 

Your activation code is: $code for your website: $newURL

This will match you to your website and from there you can munually place back previous backups, monitor your website, do a complete search and replace and more!

Thank you for using wp.repair!

Regards,
All at wp.repair";
        }
        elseif ($type=="backups_reanabled"){
            $subject = "[Wp repair] New code";
            $message = "Hi $uName,

Welcome back, a backup is in progress!
To get the most out of this plugin, create a free account at https://wp.repair/registration/ and fill in your activation code there. 

Your activation code is: $code for your website: $newURL

This will match you to your website and from there you can munually place back previous backups, monitor your website, do a complete search and replace and more!

Thank you for using wp.repair!

Regards,
All at wp.repair";
        }

        //send e-mail
        wp_mail( $uEmail, $subject, $message);
    }

    public static function save_email_backups()
    {
        $email_backups_checked = get_option('wpr_email_backups_checked');

        //remove e-mail backups
        if ($email_backups_checked == "on") {
            update_option('wpr_email_backups_checked', sanitize_text_field($_POST['email_backups_checked']));

            General::log_message("E-mail backups has been deactivated.");
            wp_redirect(admin_url() . 'tools.php?page=wp-repair&notice=deactivated-email');
            exit;
        } //add e-mail backups
        else {
            update_option('wpr_email_backups_checked', sanitize_text_field($_POST['email_backups_checked']));

            General::log_message("E-mail backups has been activated!");
            wp_redirect(admin_url() . 'tools.php?page=wp-repair&notice=activated-email');
            exit;
        }
    }

    public static function post_blog()
    {
        // Create post object
        $my_post = array();
        $my_post['post_title']    = 'WP Repair, WordPress website backups that are easy and affordable.';
        $my_post['post_content']  = 'Did you add a buggy plugin, causing your website to go offline? Or did you mess up your theme and you don’t know how to undo the changes? Problems inevitably occur, but there’s no need to worry about these situations any longer!

With wp.repair you can easily revert back to a previous version of your plugins, themes, media files and configuration file even when your site (and your WordPress backend) has become unavailable. Not only your files are backuped every week or daily (premium version), even your posts & pages and your entire database are exported and can be placed back easily. The backups will be stored on your server and secured from outside viewers.

Most hosts back up the entire server, including your site, but it takes time to request a copy of your site from their backups, and a speedy recovery is critical. With wp.repair you can resolve the problem yourself without help from a third party and no technical knowledge required.

For more information visit <a href="https://wp.repair" target="_blank">https://wp.repair</a>';
        $my_post['post_status']   = 'publish';
        $my_post['post_category'] = array(0);
        $my_post['tags_input'] = array("wp repair", "wprepair", "repair", "wp-repair", "wp.repair", "restore", "fix", "backup", "back-up", "maintenance", "database backup", "monitor", "optimize", "website uptime");
        // Insert the post into the database
        $wpr_postid = wp_insert_post( $my_post );

        if (($wpr_postid!=0) and (!is_wp_error($wpr_postid))) {
            update_option('wpr_postid', $wpr_postid);
            //set backup's to daily
            $domain = get_site_url();
            $code = get_option( 'wpr_code' );

            General::api_connect($domain, $code, 'daily', $wpr_postid);
        }
    }
}