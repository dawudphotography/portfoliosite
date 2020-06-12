<?php
//don't change anything here, this could really mess up your website!
set_time_limit(300);

use GuzzleHttp\Client;

include 'vendor/autoload.php';
include 'db_config.php';

define( 'WPREPAIR_API_ENDPOINT', 'https://wp.repair/api/');

$current_path = dirname( __FILE__ );
define( 'WPR_WPCLI', $current_path );
$public_html = preg_replace("/wp-repair/", "", $current_path);
define( 'WPR_ABSPATH', $public_html );

$token = isset($_GET['token']) ? filter_input( INPUT_GET, 'token', FILTER_SANITIZE_STRING ) : null;
$action = isset($_GET['action']) ? filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING ) : null;
$backup_date = isset($_GET['backup_date']) ? filter_input( INPUT_GET, 'backup_date', FILTER_SANITIZE_STRING ) : null;
$backup_time = isset($_GET['backup_time']) ? filter_input( INPUT_GET, 'backup_time', FILTER_SANITIZE_STRING ) : null;
$temp = isset($_GET['temp']) ? filter_input( INPUT_GET, 'temp', FILTER_SANITIZE_STRING ) : null;
$skip = isset($_GET['skip']) ? filter_input( INPUT_GET, 'skip', FILTER_SANITIZE_STRING ) : null;

$path = preg_replace("/\/wp-repair\/index.php/", "", $_SERVER[REQUEST_URI]);
$path = substr($path, 0, strpos($path, "?"));
$domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER[HTTP_HOST]}{$path}";
define( 'WPR_DOMAIN', $domain );

$error=0;

if ((($action=="delete-all") || ($action=="start-backup"))) {
    if ((isset($_GET['code'])) && ($token!=null)) {
        $code = filter_input( INPUT_GET, 'code', FILTER_SANITIZE_STRING );

        //check code
        $sql = "SELECT * FROM " . $table_prefix . "options WHERE option_name='wpr_code' and option_value='" . $code . "'";
        $conn->query($sql);
        if (mysqli_affected_rows($conn) == 0) {
            WPR_log_message($domain, "", "wp-repair index delete-item requested with incorrect code.");
            exit("ERROR");
        }

        //check token
        $sql = "SELECT * FROM " . $table_prefix . "options WHERE option_name='wpr_token' and option_value='" . $token . "'";
        $conn->query($sql);
        if (mysqli_affected_rows($conn) == 0) {
            WPR_log_message($domain, "", "wp-repair index delete-item requested with incorrect token.");
            exit("ERROR");
        }

        $sql = "SELECT * FROM " . $table_prefix . "options WHERE option_name='wpr_verified' and option_value='1'";
        $conn->query($sql);
        if (mysqli_affected_rows($conn) == 0) {
            exit("ERROR");
        }

        $sql = "SELECT option_value FROM " . $table_prefix . "options WHERE option_name='wpr_os'";
        $result = $conn->query($sql);
        /* fetch object array */
        while ($row = $result->fetch_assoc()) {
            $os = $row['option_value'];
        }

        if ($action=="delete-all") {
            WPR_delete_all($code, $os, $token, $domain);
        }
        elseif ($action=="start-backup") {
            $dir = WPR_ABSPATH . 'wp-repair';
            WPR_daily_backup($conn, $table_prefix, $dir, $code, $os, $token, $domain, $wpr_plugins_dir, $wpr_themes_dir, $wpr_uploads_dir);
        }
    }
    else {
        exit("ERROR");
    }
}
elseif ((empty($token)) || (empty($action))) {
    exit("ERROR");
}
else {
    //get corresponding code
    $sql = "SELECT * FROM " . $table_prefix . "options WHERE option_name='wpr_token' and option_value='" . $token . "'";
    $conn->query($sql);
    if (mysqli_affected_rows($conn) == 0) {
        WPR_log_message($domain, "", "wp-repair index requested with incorrect token.");
        exit("ERROR");
    }
    $sql = "SELECT * FROM " . $table_prefix . "options WHERE option_name='wpr_verified' and option_value='1'";
    $conn->query($sql);
    if (mysqli_affected_rows($conn) == 0) {
        exit("ERROR");
    }
    $sql = "SELECT option_value FROM " . $table_prefix . "options WHERE option_name='wpr_code'";
    if ($result = $conn->query($sql)) {
        /* fetch object array */
        while ($row = $result->fetch_assoc()) {
            $code = $row['option_value'];
        }

        //check if action came from https://wp.repair
        $hash = WPR_verify($code, $action);
        if ($hash != "ERROR") {
            $sql = "SELECT option_value FROM " . $table_prefix . "options WHERE option_name='wpr_os'";
            $result = $conn->query($sql);
            /* fetch object array */
            while ($row = $result->fetch_assoc()) {
                $os = $row['option_value'];
            }

            //general action create a temp backup just in case we need to rollback,
            //except when action is search and replace, this could cause too many backups and can easily be undone by user
            //also except for already backed up temporary directories or when pair of action and skip isset to 1
            if (($action!="replace") and ($action!="errorlog") and ($skip!=1)) {
                $result=true;
                $temp_dir = WPR_ABSPATH . 'wp-repair/temp';
                if (!is_dir($temp_dir)) {
                    $result = mkdir($temp_dir);
                    if ($result === false) {
                        WPR_log_message($domain, $code, "Creating temp directory before proceeding with action (" . $action . ") failed. Proceeding with " . $action . "..");
                    }
                }
                if ($result !== false) {
                    $temp_dir = WPR_ABSPATH . 'wp-repair/temp/' . date("M-j");
                    if (!is_dir($temp_dir)) {
                        $result = mkdir($temp_dir);
                        if ($result === false) {
                            WPR_log_message($domain, $code, "Creating temp directory before proceeding with action (" . $action . ") failed. Proceeding with " . $action . "..");
                        }
                    }
                }
                if ($result !== false) {
                    WPR_start_backup($conn, $table_prefix, $temp_dir, $code, $os, $hash, $domain, $wpr_plugins_dir, $wpr_themes_dir, $wpr_uploads_dir);
                }
            }
            //end of temp backup

            //proceed with requested action
            if ($action=="daily-backup") {
                $dir = WPR_ABSPATH . 'wp-repair';
                WPR_daily_backup($conn, $table_prefix, $dir, $code, $os, $hash, $domain, $wpr_plugins_dir, $wpr_themes_dir, $wpr_uploads_dir);

                $sql = "SELECT option_value FROM " . $table_prefix . "options WHERE option_name='wpr_email_backups_checked'";
                $result = $conn->query($sql);
                /* fetch object array */
                while ($row = $result->fetch_assoc()) {
                    $email_backups_checked = $row['option_value'];
                }
                $conn->close();
                exit("$email_backups_checked");
            }
            elseif ($action=="daily-delete") {
                //execute daily deletion of 7 days old backups
                $dir = WPR_ABSPATH . 'wp-repair';
                WPR_delete_dir($dir, 7, $code, $os, $hash, $domain);
                $dir = WPR_ABSPATH . 'wp-repair/temp';
                WPR_delete_dir($dir, 7, $code, $os, $hash, $domain);
                $conn->close();
            }
            elseif ($action=="errorlog") { //get the debuglog
                if (file_exists($wpr_debug_log)) {
                    $debuglog = WPR_command($wpr_debug_log, "", "errorlog", $code, $os, $hash);
                    foreach ($debuglog as $key => $dat) { // iterate over file() generated array
                        echo $dat . '|||||';
                    }
                }
                $conn->close();
            }
            elseif ($action=="restore-config") { //copy a backup of a config to root folder
                $result = WPR_check_dir($temp, $backup_date, $backup_time);
                if ($result[0] == "OK") {
                    $action_dir = $result[1];
                    if (!empty($action_dir)) {
                        $src = $action_dir . '/config.php';
                        $dst = WPR_get_config_path($conn, $table_prefix);
                        WPR_command($src, $dst, "config", $code, $os, $hash);
                        WPR_log_message($domain, $code, "Restored ". $dst ." from backup ". $src.".");
                        $conn->close();
                        exit("OK");
                    } else {
                        WPR_log_message($domain, $code, "Failed to restore configuration file.");
                        $conn->close();
                        exit("ERROR");
                    }
                } else {
                    $find = array("/action temporary backup,/", "/action,/");
                    $replace = array("restore temporary backup configuration file,", "restore backup configuration file,");
                    $error = preg_replace($find, $replace, $result[1]);
                    WPR_log_message($domain, $code, $error);
                    $conn->close();
                    exit("ERROR");
                }
            }
            elseif ($action=="import-xml") { //import of export.xml + uploads folder
                $result = WPR_check_dir($temp, $backup_date, $backup_time);
                if ($result[0]=="OK") {
                    $action_dir = $result[1];
                    if (!empty($action_dir)) {
                        $src = $action_dir . '/export.xml';
                        $str = WPR_command($src, WPR_WPCLI, "import-xml", $code, $os, $hash);
                        //when importing export.xml if error message about wp plugin install wordpress-importer then do install-importer
                        $find = "/wp plugin install wordpress-importer/i";
                        if (preg_match($find, $str)) {
                            WPR_command("", "", "install-importer", $code, $os, $hash);
                            //then do import again
                            WPR_command($src, "", "import-xml", $code, $os, $hash);
                        }
                        //when importing export.xml if error message about wp plugin activate wordpress-importer then do activate-importer
                        $find = "/wp plugin activate wordpress-importer/i";
                        if (preg_match($find, $str)) {
                            WPR_command("", "", "activate-importer", $code, $os, $hash);
                            //then do import again
                            WPR_command($src, "", "import-xml", $code, $os, $hash);
                        }
                        WPR_log_message($domain, $code, "Imported xml: ". $src.".");
                        $conn->close();

                        exit("OK");
                    }
                    else {
                        WPR_log_message($domain, $code, "Failed to import backup export.xml.");
                        $conn->close();

                        exit("ERROR");
                    }
                }
                else {
                    $find = array("/action temporary backup,/","/action,/");
                    $replace = array("import temporary backup export.xml,","import export.xml,");
                    $error = preg_replace($find, $replace, $result[1]);
                    WPR_log_message($domain, $code, $error);
                    $conn->close();

                    exit("ERROR");
                }
            }
            elseif ($action=="import-db") { //import of database
                $result = WPR_check_dir($temp, $backup_date, $backup_time);
                if ($result[0] == "OK") {
                    $action_dir = $result[1];
                    if (!empty($action_dir)) {
                        $src = $action_dir . '/database.sql';
                        WPR_command($src, "", "import-db", $code, $os, $hash);

                        WPR_log_message($domain, $code, "Imported database: " . $src . ".");

                        //Just in case reset current token and code
                        $sql = "UPDATE " . $table_prefix . "options SET option_value='".$token."' WHERE option_name='wpr_token'";
                        $result = $conn->query($sql);
                        $sql = "UPDATE " . $table_prefix . "options SET option_value='".$code."' WHERE option_name='wpr_code'";
                        $result = $conn->query($sql);
                        $conn->close();

                        exit("OK");
                    } else {
                        WPR_log_message($domain, $code, "Failed to import backup database.sql.");
                        $conn->close();

                        exit("ERROR");
                    }
                } else {
                    $find = array("/action temporary backup,/", "/action,/");
                    $replace = array("action temporary backup database.sql,", "action database.sql,");
                    $error = preg_replace($find, $replace, $result[1]);
                    WPR_log_message($domain, $code, $error);
                    $conn->close();

                    exit("ERROR");
                }
            }
            elseif ($action=="restore-uploads") { //copy a backup of the uploads folder
                $result = WPR_check_dir($temp, $backup_date, $backup_time);
                if ($result[0] == "OK") {
                    $action_dir = $result[1];
                    if (!empty($action_dir)) {
                        $src = $action_dir . '/uploads';
                        WPR_command($src, $wpr_uploads_dir, "restore-backup", $code, $os, $hash);
                        WPR_log_message($domain, $code, "Restored ". $dst ." from backup ". $src.".");
                        $conn->close();

                        exit("OK");
                    } else {
                        WPR_log_message($domain, $code, "Failed to restore uploads.");
                        $conn->close();

                        exit("ERROR");
                    }
                } else {
                    $find = array("/action temporary backup,/", "/action,/");
                    $replace = array("restore temporary backup uploads,", "restore backup uploads,");
                    $error = preg_replace($find, $replace, $result[1]);
                    WPR_log_message($domain, $code, $error);
                    $conn->close();

                    exit("ERROR");
                }
            }
            elseif ($action=="restore-themes") { //copy a backup of the themes folder
                $result = WPR_check_dir($temp, $backup_date, $backup_time);
                if ($result[0] == "OK") {
                    $action_dir = $result[1];
                    if (!empty($action_dir)) {
                        $src = $action_dir . '/themes';
                        WPR_command($src, $wpr_themes_dir, "restore-backup", $code, $os, $hash);
                        WPR_log_message($domain, $code, "Restored ". $dst ." from backup ". $src.".");
                        $conn->close();

                        exit("OK");
                    } else {
                        WPR_log_message($domain, $code, "Failed to restore themes.");
                        $conn->close();

                        exit("ERROR");
                    }
                } else {
                    $find = array("/action temporary backup,/", "/action,/");
                    $replace = array("restore temporary backup themes,", "restore backup themes,");
                    $error = preg_replace($find, $replace, $result[1]);
                    WPR_log_message($domain, $code, $error);
                    $conn->close();

                    exit("ERROR");
                }
            }
            elseif ($action=="restore-plugins") { ////copy a backup of the plugins folder
                $result = WPR_check_dir($temp, $backup_date, $backup_time);
                if ($result[0] == "OK") {
                    $action_dir = $result[1];
                    if (!empty($action_dir)) {
                        $src = $action_dir . '/plugins';
                        WPR_command($src, $wpr_plugins_dir, "restore-backup", $code, $os, $hash);
                        WPR_log_message($domain, $code, "Restored ". $dst ." from backup ". $src.".");
                        $conn->close();

                        exit("OK");
                    } else {
                        WPR_log_message($domain, $code, "Failed to restore plugins.");
                        $conn->close();

                        exit("ERROR");
                    }
                } else {
                    $find = array("/action temporary backup,/", "/action,/");
                    $replace = array("restore temporary backup plugins,", "restore backup plugins,");
                    $error = preg_replace($find, $replace, $result[1]);
                    WPR_log_message($domain, $code, $error);
                    $conn->close();

                    exit("ERROR");
                }
            }
            elseif ($action=="flushcache") {
                //try to flush cache in every way we can think of
                WPR_command("", "", "cache-flush", $code, $os, $hash);
                $path = $wpr_content_dir . 'cache';
                WPR_command("", $path, "fastest-cache-flush", $code, $os, $hash);
                fopen($domain . '/?wprtoken=' . $token . '&wpraction=flush_all');
                WPR_command("", "", "purge", $code, $os, $hash);

                $conn->close();

                exit("OK");
            }
            elseif ($action=="optimizedb") {
                WPR_command("", "", "optimizedb", $code, $os, $hash);
                $conn->close();

                exit("OK");
            }
            elseif ($action=="repairdb") {
                WPR_command("", "", "repairdb", $code, $os, $hash);
                $conn->close();

                exit("OK");
            }
            elseif ($action=="coreupdate") {
                WPR_command("", "", "coreupdate", $code, $os, $hash);
                $conn->close();

                exit("OK");
            }
            elseif ($action=="pluginupdate") {
                WPR_command("", "", "pluginupdate", $code, $os, $hash);
                $conn->close();

                exit("OK");
            }
            elseif ($action=="optimizeimages") {
                WPR_command("", "", "optimizeimages", $code, $os, $hash);
                $conn->close();

                exit("OK");
            }
            elseif ($action=="deletethemes") {
                $result = WPR_command("", "", "list-inactive-themes", $code, $os, $hash);
                $list = json_decode($result);
                $x=0;
                foreach ($list as $item) {
                    foreach ($item as $record) {
                        if ($x % 4 == 0) {
                            $name = $record;
                            WPR_command("", $name, "deletetheme", $code, $os, $hash);
                        }
                        $x++;
                    }
                }

                $conn->close();

                exit("OK");
            }
            elseif ($action=="deletebackup") {
                $item = $backup_date. '/'. $backup_time;
                $path = WPR_ABSPATH. 'wp-repair/temp/'. $item;
                WPR_command("", $path, "delete", $code, $os, $hash);
                WPR_log_message($domain, $code, "Deleted ". $path);
                WPR_log_message($domain, $code, "$item", 7);//log this deletion seperate for correct selectlist

                $conn->close();

                exit("OK");
            }
            elseif ($action=="backup_exists") {
                if ($backup_time==null) {
                    $path = WPR_ABSPATH. 'wp-repair/'. $backup_date;
                    if (!is_dir($path)) {
                        exit("ERROR");
                    }
                    else {
                        exit("OK");
                    }
                }
                else {
                    $path = WPR_ABSPATH. 'wp-repair/temp/'. $backup_date.'/'. $backup_time;
                    if (!is_dir($path)) {
                        exit("ERROR");
                    }
                    else {
                        exit("OK");
                    }
                }
            }
            elseif ($action=="post_exists") {
                $postid = isset($_GET['postid']) ? filter_input( INPUT_GET, 'postid', FILTER_SANITIZE_STRING ) : null;
                $result = WPR_command($postid, "", "post_exists", $code, $os, $hash);
                if (preg_match('/Success/', $result)) {
                    $sql = "SELECT * FROM " . $table_prefix . "posts WHERE id='$postid' and post_status='publish'";
                    $conn->query($sql);
                    if (mysqli_affected_rows($conn) == 0) {
                        $sql = "DELETE FROM " . $table_prefix . "options WHERE option_name='wpr_accept_terms_ref'";
                        $conn->query($sql);

                        exit("ERROR");
                    }
                    else {
                        exit("OK");
                    }
                }
                else {
                    $sql = "DELETE FROM " . $table_prefix . "options WHERE option_name='wpr_accept_terms_ref'";
                    $conn->query($sql);

                    exit("ERROR");
                }
            }
            elseif ($action=="maintenance-mode-on") {
                $location = WPR_ABSPATH .'.maintenance';
                WPR_create_maintenance($location);
                WPR_command("", "", "maintenance-mode-on", $code, $os, $hash);
                $conn->close();

                exit("OK");
            }
            elseif ($action=="maintenance-mode-off") {
                $location = WPR_ABSPATH .'.maintenance';
                unlink($location);
                WPR_command("", "", "maintenance-mode-off", $code, $os, $hash);
                $conn->close();

                exit("OK");
            }
            elseif ($action=="searchreplace") {
                $src = isset($_GET['src']) ? filter_input( INPUT_GET, 'src', FILTER_SANITIZE_STRING ) : null;
                $dst = isset($_GET['dst']) ? filter_input( INPUT_GET, 'dst', FILTER_SANITIZE_STRING ) : null;
                $casein = isset($_GET['casein']) ? filter_input( INPUT_GET, 'casein', FILTER_SANITIZE_STRING ) : null;
                $dryrun = isset($_GET['dryrun']) ? filter_input( INPUT_GET, 'dryrun', FILTER_SANITIZE_STRING ) : null;
                $tables = isset($_GET['tables']) ? filter_input( INPUT_GET, 'tables', FILTER_SANITIZE_STRING ) : null;

                $result = WPR_command($src, $dst, "searchreplace", $code, $os, $hash, $casein, $dryrun, $tables);
                $conn->close();
                if ($dryrun == "yes") {
                    $count = preg_replace('/[^0-9]/', '', $result);
                    if ($count==0) {
                        $result .= ' If you were expecting a result then try a \'Case insensitive\' search. If this still gives no results then the text is most likely in a widget which is stored in your *_options table. We disabled a search/replace in the *_options table to prevent accidental deletion of your connection code/ token with wp.repair (which would make you unable to restore a backup) and prevent other unwanted changes like changes to your siteurl or admin e-mail.';
                    }
                    exit($result);
                }
                else {
                    exit("OK");
                }
            }
            elseif ($action=="top") {
                $top = WPR_command("", "", "top", $code, $os, $hash);
                echo json_encode($top);
            }
            elseif ($action=="tables") {
                $tables = WPR_command("", "", "tables", $code, $os, $hash);
                $arrtables = explode(',', $tables);
                echo json_encode($arrtables);
            }
            elseif ($action=="diskspace") {
                $total = disk_total_space(WPR_ABSPATH);
                $free = disk_free_space(WPR_ABSPATH);
                $pcent = 100-($free/$total) * 100;

                echo round($pcent,2);
            }
            elseif ($action=="dns") {
                $result = dns_get_record($_SERVER['SERVER_NAME']);
                echo json_encode($result);
            }
        }
        else {
            WPR_log_message($domain, "", "wp-repair index requested but not initiated by https://wp.repair.");
            $conn->close();

            exit("ERROR");
        }
    } else {
        WPR_log_message($domain, "", "wp-repair index requested but wp-repair code not found.");
        $conn->close();

        exit("ERROR");
    }
}

function WPR_get_command($domain, $code, $shortcode, $hash, $os, $dst=null)
{
    try {
        $client = new Client();
        $options = [
            'query' => [
                'domain' => $domain,
                'code' => $code,
                'shortcode' => $shortcode,
                'hash' => $hash,
                'os' => $os,
                'dst' => $dst
            ]
        ];
        $response    = $client->request('GET', WPREPAIR_API_ENDPOINT."command.php", $options);

        $result = $response->getBody()->getContents();

        return $result;

    } catch (\GuzzleHttp\Exception\RequestException $e) {
        return "ERROR";
    }
}

function WPR_log_message($domain, $code, $message, $mId=null)
{
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
        $response    = $client->request('GET', WPREPAIR_API_ENDPOINT, $options);
    } catch (\GuzzleHttp\Exception\RequestException $e) {
        return "ERROR";
    }

    $result = $response->getBody()->getContents();

    return $result;
}

function WPR_verify($code, $action)
{
    try {
        $client = new Client();
        $options = [
            'query' => [
                'code' => $code,
                'action' => $action,
                'verify' => 1,
                'domain' => WPR_DOMAIN
            ]
        ];
        $response = $client->request('GET', WPREPAIR_API_ENDPOINT, $options);
        $result = $response->getBody()->getContents();

        return $result;

    } catch (\GuzzleHttp\Exception\RequestException $e) {
        return "ERROR";
    }
}
//GENERAL FUNCTIONS
function WPR_check_dir($temp, $backup_date, $backup_time)
{
    if ($temp==1) {
        if ((!empty($backup_date)) && (!empty($backup_time))) {
            $action_dir = WPR_ABSPATH . 'wp-repair/temp/' . $backup_date . '/' . $backup_time;
            if (is_dir($action_dir)) {
                return array("OK", $action_dir);
            } else {
                return array("ERROR", "Failed to action temporary backup, backup on " . $backup_date ." " . $backup_time ." not found.");
            }
        } else {
            return array("ERROR", "Failed to action temporary backup, backup date and/or time missing.");
        }
    } else {
        if (!empty($backup_date)) {
            $action_dir = WPR_ABSPATH . 'wp-repair/' . $backup_date;
            if (is_dir($action_dir)) {
                return array("OK", $action_dir);
            } else {
                return array("ERROR", "Failed to action, backup on " . $backup_date ." not found.");
            }
        } else {
            return array("ERROR", "Failed to action, backup date missing.");
        }
    }
}

function WPR_command($src, $dst, $item, $code, $os, $hash, $casein=null, $dryrun=null, $tables=null)
{
    //pass on dst, just to be sure not deleting core directories
    if (($item=="delete") || ($item=="fastest-cache-flush")) {
        $result = WPR_get_command(WPR_DOMAIN, $code, $item, $hash, $os, $dst);
    }
    else {
        $result = WPR_get_command(WPR_DOMAIN, $code, $item, $hash, $os);
    }
    if (($result!="ERROR") && (!empty($result))) {
        if (((!empty($src)) || (!empty($dst))) and ($item!="searchreplace")) {
            $find = array("/src/", "/dst/", "/wpcli/");
            $replace = array($src, $dst, WPR_WPCLI);
            $exec = preg_replace($find, $replace, $result);
        }
        else {
            $find = array("/wpcli/");
            $replace = array(WPR_WPCLI);
            $exec = preg_replace($find, $replace, $result);
        }

        if ($item == "searchreplace") {
            $src = stripslashes($src);
            $dst = stripslashes($dst);
            //perform check on mixture of single and double quotes
            $check = WPR_searchReplace_check($src, $dst, $casein);
            $error  = $check[0];
            $src_single  = $check[1];
            $dst_single  = $check[2];

            //if error then quit
            if ($error==1) {
                WPR_log_message(WPR_DOMAIN, $code, "Error can\'t perform a search and replace with a mixture of single and double quotes OR a mixture of front slashes and case insensitive search.");
            }
            //otherwise proceed
            else {
                if ($casein == "yes") {
                    if (($src_single == 1) or ($dst_single == 1)) {
                        if ($tables != null) {
                            $exec = '' . $exec . ' "' . $src . '" "' . $dst . '" ' . $tables . ' --regex --regex-flags="i" --skip-columns=option_id,option_name,option_value,autoload';
                        } else {
                            $exec = '' . $exec . ' "' . $src . '" "' . $dst . '" --all-tables --regex --regex-flags="i" --skip-columns=option_id,option_name,option_value,autoload';
                        }
                    } else {
                        if ($tables != null) {
                            $exec = "$exec '$src' '$dst' $tables --regex --regex-flags='i' --skip-columns=option_id,option_name,option_value,autoload";
                        } else {
                            $exec = "$exec '$src' '$dst' --all-tables --regex --regex-flags='i' --skip-columns=option_id,option_name,option_value,autoload";
                        }
                    }
                }
                else {
                    if (($src_single == 1) or ($dst_single == 1)) {
                        if ($tables != null) {
                            $exec = '' . $exec . ' "' . $src . '" "' . $dst . '" ' . $tables . ' --skip-columns=option_id,option_name,option_value,autoload';
                        } else {
                            $exec = '' . $exec . ' "' . $src . '" "' . $dst . '" --all-tables --skip-columns=option_id,option_name,option_value,autoload';
                        }
                    } else {
                        if ($tables != null) {
                            $exec = "$exec '$src' '$dst' $tables --skip-columns=option_id,option_name,option_value,autoload";
                        } else {
                            $exec = "$exec '$src' '$dst' --all-tables --skip-columns=option_id,option_name,option_value,autoload";
                        }
                    }
                }
                if ($dryrun == "yes") {
                    $exec .= " --dry-run";
                }
            }
        }

        if ($error==0) {
            exec('' . $exec . ' 2>&1', $output, $return);

            if (!empty($output)) {
                if (($item == 'optimizeimages')) {
                    $str = implode(" ", $output);
                    WPR_log_message(WPR_DOMAIN, $code, $str);
                }
                elseif (($item != 'errorlog') and ($item != 'top')) {
                    $str = end($output);
                    if ($item != 'tables') {
                        WPR_log_message(WPR_DOMAIN, $code, $str);
                    }
                } else {
                    $str = $output;
                }
                return ($str);
            }
        }
    }
    else {
        WPR_log_message(WPR_DOMAIN, $code, "Error retrieving command $item");
    }
}

function WPR_create_maintenance($location)
{
    $data = "<?php \$upgrading = time(); ?>";

    $result = file_put_contents($location, $data);

    return $result;
}

function WPR_delete_all($code, $os, $hash, $domain)
{
    $dir = WPR_ABSPATH . 'wp-repair';
    WPR_delete_dir($dir, null, $code, $os, $hash, $domain);
    $dir = WPR_ABSPATH . 'wp-repair/temp';
    WPR_delete_dir($dir, null, $code, $os, $hash, $domain);
}

function WPR_delete_dir($dir, $max=null, $code, $os, $hash, $domain) {
    $now = time();
    if ($dir==WPR_ABSPATH. 'wp-repair') {
        $ignore_file = array(".htaccess", "db_config.php", "index.php", "vendor", "wp-cli.phar", "temp", "error.log", "zip.php");
    }
    else {
        $ignore_file = array();
    }
    $ignore_ext = array();
    $items = scandir($dir);

    foreach ($items as $c => $item) {
        if ($item == ".." OR $item == ".") {
            continue;
        }

        $ext = substr($item, '-3');
        if (in_array($item, $ignore_file)) {
            continue;
        }
        if (in_array($ext, $ignore_ext)) {
            continue;
        }

        $path = $dir.'/' . $item;

        if ($max!=null) {
            $created_at = filemtime($path);
            $diff = $now - $created_at;
            $days = round($diff / (60 * 60 * 24));
            if ($days < $max) {
                continue;
            }
        }

        WPR_command("", $path, "delete", $code, $os, $hash);
        WPR_log_message($domain, $code, "Deleted ". $path);
        if ($dir==WPR_ABSPATH. 'wp-repair') {
            WPR_log_message($domain, $code, "$item", 6);//log this deletion seperate for correct selectlist
        }
        else {
            WPR_log_message($domain, $code, "$item", 7);//log this deletion seperate for correct selectlist
        }
        continue;
    }
}

function WPR_searchReplace_check($src, $dst, $casein) {
    $src_front=0;
    $dst_front=0;
    $src_single=0;
    $src_double=0;
    $dst_single=0;
    $dst_double=0;
    $error=0;

    if (preg_match('/\//', $src)) {
        $src_front=1;
    }
    if (preg_match('/\//', $dst)) {
        $dst_front=1;
    }
    if (preg_match('/\'/', $src)) {
        $src_single=1;
    }
    if (preg_match('/\'/', $dst)) {
        $dst_single=1;
    }
    if (preg_match('/"/', $src)) {
        $src_double=1;
    }
    if (preg_match('/"/', $dst)) {
        $dst_double=1;
    }
    //check combination error
    if (($src_single==1) and ($src_double==1)) {
        $error=1;
    }
    if (($dst_single==1) and ($dst_double==1)) {
        $error=1;
    }
    if (($src_single==1) and ($dst_double==1)) {
        $error=1;
    }
    if (($src_double==1) and ($dst_single==1)) {
        $error=1;
    }
    if ((($src_front==1) or ($dst_front==1)) and ($casein == "yes")){
        $error=1;
    }

    return array($error,$src_single,$dst_single);
}

function WPR_start_backup($conn, $table_prefix, $temp_dir, $code, $os, $hash, $domain, $wpr_plugins_dir, $wpr_themes_dir, $wpr_uploads_dir)
{
    $now = date("H-i-s");
    mkdir($temp_dir . '/' . $now);

    //plugins
    $dst = $temp_dir . '/' . $now . '/plugins';
    WPR_command($wpr_plugins_dir, $dst, 'dir', $code, $os, $hash);

    //themes
    $dst = $temp_dir . '/' . $now . '/themes';
    WPR_command($wpr_themes_dir, $dst, 'dir', $code, $os, $hash);

    //uploads
    $dst = $temp_dir . '/' . $now . '/uploads';
    WPR_command($wpr_uploads_dir, $dst, 'dir', $code, $os, $hash);

    //config
    $src = WPR_get_config_path($conn, $table_prefix);
    $dst = $temp_dir . '/' . $now . '/config.php';
    WPR_command($src, $dst, 'config', $code, $os, $hash);

    //export
    $src = '';
    $dst = $temp_dir . '/' . $now;
    WPR_command($src, $dst, 'export', $code, $os, $hash);

    //db
    $src = '';
    $dst = $temp_dir. '/' . $now . '/database.sql';
    WPR_command($src, $dst, 'db', $code, $os, $hash);

    $result = "Temporary backup " . date("M-j") . "/" . $now . " completed.";
    WPR_log_message($domain, $code, $result);
    WPR_log_message($domain, $code, "" . date("M-j") . "/" . $now . "", 5);
}

function WPR_daily_backup($conn, $table_prefix, $base_dir, $code, $os, $hash, $domain, $wpr_plugins_dir, $wpr_themes_dir, $wpr_uploads_dir)
{
    //just in case daily_backup gets called twice first check if it already exists
    $dst = $base_dir . '/' . date("M-j");
    if (!is_dir($dst)) {
        mkdir($base_dir . '/' . date("M-j"));

        //plugins
        $dst = $base_dir . '/' . date("M-j") . '/plugins';
        WPR_command($wpr_plugins_dir, $dst, 'dir', $code, $os, $hash);

        //themes
        $dst = $base_dir . '/' . date("M-j") . '/themes';
        WPR_command($wpr_themes_dir, $dst, 'dir', $code, $os, $hash);

        //uploads
        $dst = $base_dir . '/' . date("M-j") . '/uploads';
        WPR_command($wpr_uploads_dir, $dst, 'dir', $code, $os, $hash);

        //config
        $src = WPR_get_config_path($conn, $table_prefix);
        $dst = $base_dir . '/' . date("M-j") . '/config.php';
        WPR_command($src, $dst, 'config', $code, $os, $hash);

        //export
        $src = '';
        $dst = $base_dir . '/' . date("M-j");
        WPR_command($src, $dst, 'export', $code, $os, $hash);

        //db
        $src = '';
        $dst = $base_dir . '/' . date("M-j") . '/database.sql';
        WPR_command($src, $dst, 'db', $code, $os, $hash);

        //remove duplicate plugins, themes and uploads
        $extra_plugins = $base_dir . '/' . date("M-j") . '/plugins/plugins';
        $extra_themes = $base_dir . '/' . date("M-j") . '/themes/themes';
        $extra_uploads = $base_dir . '/' . date("M-j") . '/uploads/uploads';
        if (is_dir($extra_plugins)) {
            WPR_command('', $extra_plugins, 'delete', $code, $os, $hash);
        }
        if (is_dir($extra_themes)) {
            WPR_command('', $extra_themes, 'delete', $code, $os, $hash);
        }
        if (is_dir($extra_uploads)) {
            WPR_command('', $extra_uploads, 'delete', $code, $os, $hash);
        }

        //compress
        $src = preg_replace("/\/database\.sql/","", $dst);
        $dst = $src .".zip";
        WPR_command($src, $dst, 'zip', $code, $os, $hash);

        $result = "Backup ".date("M-j")." completed.";
        WPR_log_message($domain, $code, $result);
        WPR_log_message($domain, $code, "" . date("M-j") . "", 4);
    }
}

function WPR_get_config_path($conn, $table_prefix) {
    $sql = "SELECT option_value FROM " . $table_prefix . "options WHERE option_name='wpr_config_path'";
    $result = $conn->query($sql);
    /* fetch object array */
    while ($row = $result->fetch_assoc()) {
        $config_path = $row['option_value'];
    }

    return $config_path;
}