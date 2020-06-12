<?php

namespace WPREPAIR;

class WPRaccess
{
    public static function add_access($htaccess)
    {
        $data = "# BEGIN wp.repair
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_URI} !^/(wp-repair|wp-repair/.*)$
</IfModule>
# END wp.repair";

        $result = file_put_contents($htaccess, $data, FILE_APPEND);

        return $result;

    }

    public static function remove_access($htaccess)
    {
        $before = "# BEGIN wp.repair";
        $after  = "# END wp.repair";

        $fp = fopen($htaccess, "r");
        $contents = fread($fp, filesize($htaccess));
        fclose($fp);

        //something went wrong don't mess with this further
        if (empty($contents)) {
            return false;
        }
        else {
            $part1 = strstr($contents, $before, true);
            $part2 = strstr($contents, $after);
            $part2 = str_replace($after,"", $part2);
            $newData = "".$part1." ".$part2."";
            $result = file_put_contents($htaccess, $newData);

            return $result;
        }
    }

    public static function protect_backups($htaccess)
    {
        $data = "# BEGIN wp.repair
# DISABLE CACHING
<filesMatch \"\.(zip|phar|php)$\">
  FileETag None
  <ifModule mod_headers.c>
     Header unset ETag
     Header set Cache-Control \"max-age=0, no-cache, no-store, must-revalidate\"
     Header set Pragma \"no-cache\"
     Header set Expires \"Wed, 11 Jan 1984 05:00:00 GMT\"
  </ifModule>
</filesMatch>

Order deny,allow
Deny from all
<FilesMatch \"^(index|zip)\.php$\">
Allow from all
</FilesMatch>
Allow from 127.0.0.1
# END wp.repair";

        $result = file_put_contents($htaccess, $data);

        return $result;

    }

    public static function db_config($location)
    {
        global $wpdb;

        $wpr_plugins_dir = plugin_dir_path( __DIR__ );
        $wpr_plugins_dir = preg_replace("/\/wp-repair\//","",$wpr_plugins_dir);
        $wpr_themes_dir = get_theme_root();
        $wpr_uploads_dir = wp_upload_dir()["basedir"];
        $wpr_content_dir = preg_replace("/uploads/","",$wpr_uploads_dir);

        $src = ini_get( 'error_log');
        if (!file_exists($src)) {
            $src = $wpr_content_dir . 'debug.log';
            if (!file_exists($src)) {
                $src = ExecCommands::command("", "", "get-wp-debug-log");
                if (file_exists($src)) {
                    $wpr_debug_log = $src;
                }
            }
            else {
                $wpr_debug_log = $src;
            }
        }
        else {
            $wpr_debug_log = $src;
        }

        $find = "/:/";
        if (preg_match($find,DB_HOST)) {
            list($wpr_host,$wpr_port) = explode(":",DB_HOST);
        }
        else {
            $wpr_host = DB_HOST;
        }
        $data = '<?php
$host = "'.$wpr_host.'";
$user = "'.DB_USER.'";
$pass = "'.DB_PASSWORD.'";
$name = "'.DB_NAME.'";';
if (preg_match($find,DB_HOST)) {
    $data .= '
    $port = "'.$wpr_port.'";';
}
$data .= '
$table_prefix = "'.$wpdb->prefix.'";
$wpr_plugins_dir = "'.$wpr_plugins_dir.'";
$wpr_themes_dir = "'.$wpr_themes_dir.'";
$wpr_uploads_dir = "'.$wpr_uploads_dir.'";
$wpr_content_dir = "'.$wpr_content_dir.'";
$wpr_debug_log = "'.$wpr_debug_log.'";

// Create connection';
if (preg_match($find,DB_HOST)) {
    $data .= '
    $conn = new mysqli($host, $user, $pass, $name, $port);';
}
else {
    $data .= '
    $conn = new mysqli($host, $user, $pass, $name);';
}
$data .= '
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>';

        $result = file_put_contents($location, $data);

        return $result;
    }
}

