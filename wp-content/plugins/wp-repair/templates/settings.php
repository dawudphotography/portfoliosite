<?php
$img = plugin_dir_url( dirname( __FILE__ ) ).'templates/wprepair.svg';
?>
<div class="wrap">
    <!-- Need to add this to prevent notices jumping to middle of page -->
    <h1 style="display: none"></h1>
    <?php
        $status_value = get_option( 'wpr_status' );
        $backups_enabled_value = get_option('wpr_accept_terms');
        $backups_enabled_ref_value = get_option('wpr_accept_terms_ref');
        $verified_value = get_option( 'wpr_verified' );
        $code = get_option( 'wpr_code' );
        $email_backups_checked = get_option( 'wpr_email_backups_checked' );
        $current_user = wp_get_current_user();
        $uID = $current_user->ID;
    ?>

    <div class="notice notice-info" style="margin-bottom: 0;">
        <p><?php echo $status_value; ?></p>
    </div><br>

    <?php
    $notice = isset($_GET['notice']) ? filter_input( INPUT_GET, 'notice', FILTER_SANITIZE_STRING ) : null;
    if (isset($notice)) : 
    ?>
        <?php if ($notice == 'enabled_backups'): ?>
            <div class="notice notice-success is-dismissible" style="margin-bottom: 0;">
                <p><?php echo 'Backups enabled. Daily backup in process, refresh this page in about 5 minutes to download your backup.'; ?></p>
            </div><br>
        <?php endif; ?>

        <?php if ($notice == 'delete_success'): ?>
            <div class="notice notice-success is-dismissible" style="margin-bottom: 0;">
                <p><?php echo 'Backups deletion in progress. This may take up to a few minutes, refresh this page in a few minutes.'; ?></p>
            </div><br>
        <?php endif; ?>

        <?php if ($notice == 'activated-email'): ?>
            <div class="notice notice-success is-dismissible" style="margin-bottom: 0;">
                <p><?php echo 'E-mail notification backup has been activated.'; ?></p>
            </div><br>
        <?php endif; ?>

        <?php if ($notice == 'reenabled_backups'): ?>
            <div class="notice notice-success is-dismissible" style="margin-bottom: 0;">
                <p><?php echo 'Backups enabled. Daily backup (if not already processed today) in process, refresh this page in about 5 minutes.'; ?></p>
            </div><br>
        <?php endif; ?>

        <?php if ($notice == 'dropped_user'): ?>
            <div class="notice notice-success is-dismissible" style="margin-bottom: 0;">
                <p><?php echo 'Backups disabled.'; ?></p>
            </div><br>
        <?php endif; ?>

        <?php if ($notice == 'deactivated-email'): ?>
            <div class="notice notice-success is-dismissible" style="margin-bottom: 0;">
                <p><?php echo 'E-mail backups has been deactivated.'; ?></p>
            </div><br>
        <?php endif; ?>

        <?php if ($notice == 'enabled_backups_backup_error'): ?>
            <div class="notice notice-error is-dismissible" style="margin-bottom: 0;">
                <p><?php echo 'Backups enabled but backup not completed because of low disk space. Ask your hoster for more disk space.'; ?></p>
            </div><br>
        <?php endif; ?>

        <?php if ($notice == 'delete_failure'): ?>
            <div class="notice notice-error is-dismissible" style="margin-bottom: 0;">
                <p><?php echo 'All the backups have already been deleted!'; ?></p>
            </div><br>
        <?php endif; ?>

        <?php if ($notice == 'htaccess_failure'): ?>
            <div class="notice notice-error is-dismissible" style="margin-bottom: 0;">
                <p><?php echo 'Plugin won\'t work because plugin is not able to edit the .htaccess file in the public directory.'; ?></p>
            </div><br>
        <?php endif; ?>

        <?php if ($notice == 'exec_failure'): ?>
            <div class="notice notice-error is-dismissible" style="margin-bottom: 0;">
                <p><?php echo 'Plugin won\'t work because the function exec isn\'t enabled.'; ?></p>
            </div><br>
        <?php endif; ?>

        <?php if ($notice == 'dir_failure'): ?>
            <div class="notice notice-error is-dismissible" style="margin-bottom: 0;">
                <p><?php echo 'Plugin won\'t work because plugin is not able to create backup directory.'; ?></p>
            </div><br>
        <?php endif; ?>

        <?php if ($notice == 'guzzle_failure'): ?>
            <div class="notice notice-error is-dismissible" style="margin-bottom: 0;">
                <p><?php echo 'Plugin won\'t work because plugin is not able to connect to https://wp.repair.'; ?></p>
            </div><br>
        <?php endif; ?>

        <?php if ($notice == 'token_failure'): ?>
            <div class="notice notice-error is-dismissible" style="margin-bottom: 0;">
                <p><?php echo 'Plugin won\'t work because plugin failed to exchange a token with https://wp.repair. If you\'re using a cache plugin then empty your cache and try again. Other possible problem might be that you\'re on a localhost enviroment.'; ?></p>
            </div><br>
        <?php endif; ?>

        <?php if ($notice == 'windows_error'): ?>
            <div class="notice notice-error is-dismissible" style="margin-bottom: 0;">
                <p><?php echo 'Plugin does not yet work on a windows operating system.'; ?></p>
            </div><br>
        <?php endif; ?>

        <?php if ($notice == 'cron_failure'): ?>
            <div class="notice notice-error is-dismissible" style="margin-bottom: 0;">
                <p><?php echo 'Plugin won\'t work because wp cron has been disabled.'; ?></p>
            </div><br>
        <?php endif; ?>

        <?php if ($notice == 'phpversion_failure'): ?>
            <div class="notice notice-error is-dismissible" style="margin-bottom: 0;">
                <p><?php echo 'Plugin won\'t work because you have php version '.phpversion().'. The php version must be at least 5.4.0.'; ?></p>
            </div><br>
        <?php endif; ?>
    <?php endif; ?>
</div>
<div class="wrap">
    <?php
            echo "<img src='$img' width='30%' height='30%'>";
            if ($verified_value=="1") :
                echo "<h2 class='wpr-code'>Your code: $code</h2>";
            endif;
    ?>
</div>
<div class="wrap">
    <div id='loadingmsg' style='display: none;'>Processing request, please wait...</div>
    <div id='loadingover' style='display: none;'></div>
    <form action="admin-post.php?action=wpr_enable_backups" method="post">
        <?php echo "<p style='font-size: 14px;'>With wp.repair you can <b>easily revert</b> back to a previous version of your plugins, themes, media files and configuration file even <b>when your site (and your WordPress backend) has become unavailable</b>. 
Not only your files are backuped every week or daily (premium version), even your <b>posts & pages and your entire database are exported</b> and can be placed back easily.</p><br>"; ?>
        <input type ="checkbox" class="input-checkbox" name="backups_enabled" <?php if ($backups_enabled_value=="on") echo "checked"; ?> onchange="this.form.submit();"> Enable backup's</input>
    </form>
    <?php if ($backups_enabled_value=="on") { ?>
        <br>
        <form action="admin-post.php?action=wpr_enable_backups_ref" method="post">
            <input type ="checkbox" class="input-checkbox" name="backups_enabled_ref" <?php if ($backups_enabled_ref_value=="on") echo "checked"; ?> onchange="this.form.submit();">Post an item about WP Repair in my posts and <b>unlock the premium version</b></input>
        </form>
    <?php } ?>
</div>
    <?php
    $x = 0;
    if ($verified_value=="1") {
        $now = time();
        $ignore_file = array(".htaccess", "db_config.php", "index.php", "vendor", "wp-cli.phar", "temp", "error.log", "zip.php");
        $ignore_ext = array("zip");
        $items = scandir(trailingslashit(ABSPATH) . 'wp-repair');

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

            $path = trailingslashit(ABSPATH) . 'wp-repair/' . $item;
            if (!file_exists($path . '.zip')) {
                continue;
            }
            else {
                $created_at = filemtime($path . '.zip');
                $diff = $now - $created_at;
                $min = round($diff / 60);
                //continue if created less then 2 min ago, because zip might not be ready
                if ($min < 2) {
                    continue;
                }
            }

            $bytestotal = 0;
            $bytes = 0;

            if (is_dir($path)) {
                $dir = new DirectoryIterator($path);
                foreach ($dir as $fileinfo) {
                    $created_at = $fileinfo->getMTime();
                    $diff = $now - $created_at;
                    if (!$fileinfo->isDot()) {
                        $bytes = $bytes + ($fileinfo->getSize());
                    }
                }
            }

            $units = array('B', 'KB', 'MB', 'GB', 'TB');
            $bytes = max($bytes, 0);
            $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
            $pow = min($pow, count($units) - 1);
            $bytes /= (1 << (10 * $pow));
            $bytestotal = '(' . round($bytes, 2) . ' ' . $units[$pow] . ')';

            if ($x == 0) {
                echo "<br>";
                echo "<div class='wrap wpr-your-backups'>";
                echo "<h1>Your compressed backups</h1>";
                echo "<p style='font-size: 14px;'>Backup's will be permanently deleted after 7 days. You can manually download them if you like. <i>“The downloads are <b>only directly accessible by you (based on your ip-address)</b> for the period you're logged in WP.”</i><br>";
                ?>
                <form action="admin-post.php?action=email_backups" method="post">
                    <input type="checkbox" class="input-checkbox"
                           name="email_backups_checked" <?php if ($email_backups_checked == "on") echo "checked"; ?>
                           onchange="this.form.submit()"> <span
                            style='font-size: 14px;'>Receive backup notification per e-mail (this will be sent to your e-mail address registered in your account at <a href="https://wp.repair" target="_blank">wp.repair</a>)</span></input>
                    <br>
                    <span class="wpr-warning">Because the backups are very large they won't be attached to the e-mail.</span>
                </form>
                <br>
                <?php
            }

            $link =   get_site_url(). '/wp-repair/zip.php?uID='. $uID .'&file='. $item .'.zip';
            echo "<h3 class='wpr-downloads'><a href=\"$link\" class='wpr-link-download' title=\"Download " . $item . ".zip\"><span class=\"dashicons dashicons-download\"></span> $item $bytestotal</a></h3>";
            $x++;
        }

        echo "</div>";
    }
    if ($x > 0) {
        ?>
        <br>
        <form name="delform" action="admin-post.php?action=delete_backups" method="post" class="wpr-delform">
            <input type="checkbox" class="input-checkbox" name="delete_backups" onclick="WPR_checkSettings();"> <span
                    style='font-size: 14px;'><b>Delete all backups (this can't be undone!)</b></span></input>
            <br><br>
            <input type="submit" id="submit" value="Confirm delete" class="button button-primary" disabled>
        </form>
        <?php
    }
?>

