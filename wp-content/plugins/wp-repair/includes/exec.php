<?php

namespace WPREPAIR;

class ExecCommands
{
    public static function command($src, $dst, $item)
    {
        set_time_limit(300);

        $os = get_option( 'wpr_os' );
        $domain = get_site_url();
        $code = get_option( 'wpr_code' );
        $token = get_option( 'wpr_token' );
        $wpcli = trailingslashit(ABSPATH) . 'wp-repair';

        $result = General::get_command($domain, $code, $item, $token, $os);
        if (($result!="ERROR") && (!empty($result))) {
            $find = array("/src/","/dst/","/wpcli/");
            $replace = array($src,$dst,$wpcli);
            $exec = preg_replace($find, $replace, $result);

            exec(''.$exec.' 2>&1',$output,$return);
            if (!empty($output)) {
                $str = end($output);
                if ($item == "config-path") {
                    update_option('wpr_config_path', $str);
                }
                elseif ($item != "get-wp-debug-log") {
                    General::log_message($str);
                }
                else {
                    return $str;
                }
            }
        }
        else {
            General::log_message('There was an error getting command '.$item.'. Probably an expired token.');
        }
    }

    public static function os()
    {
        set_time_limit(300);

        $domain = get_site_url();
        $code = get_option( 'wpr_code' );
        $token = get_option( 'wpr_token' );

        $exec = General::get_command($domain, $code, "ls", $token, "unix");
        if (($exec!="ERROR") && (!empty($exec))) {
            exec(''.$exec.'', $output, $return);

            $exec = General::get_command($domain, $code, "location", $token, "windows");
            if (($exec!="ERROR") && (!empty($exec))) {
                if (($return != 0) and ($return != null)) {
                    update_option('wpr_os', 'windows');

                    exec(''.$exec.'', $output, $return);
                    if (!empty($output)) {
                        $str = implode(" ",$output);
                        General::log_message($str, 2);
                    }

                    if (($return == 0) or ($return == null)) {
                        $location = end($output);
                        if ($location != false) {
                            update_option('wp-repair_mysqldump.exe', $location);
                        }
                    }
                } else {
                    update_option('wpr_os', 'unix');
                }
            }
        }
    }
}
