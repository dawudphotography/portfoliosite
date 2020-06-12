<?php

namespace WPREPAIR;

/**
 * @package wp-repair
 */
/*
Plugin Name: WP Repair
Tags: repair, restore, fix, backup, monitor, maintenance, database backup, easy backup, error, undo, optimization, revert
Plugin URI: https://wp.repair
Description: This plugin will automatically make convenient <strong>backups</strong> of your plugins, themes, media files, configuration file and <strong>exports</strong> of your posts & pages and database every week or daily (premium version). It also gives you the ability to easily place back accidentally deleted files or data. Most importantly even when your site (and WordPress) has become unavailable, you'll most likely be able to <strong>repair your website</strong> and have it up and running again, without help from a third party and no technical knowledge required.
Version: 2.4.2
Author: wprepair
License: GPLv2 or later
Text Domain: wp-repair
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

Copyright 2019 wp-repair
*/

class WPREPAIR
{
    const WPREPAIR_API_ENDPOINT    = 'https://wp.repair/api/';

    public function __construct()
    {
        //plugins view
        add_filter('plugin_action_links_' . plugin_basename(__FILE__),  [$this, 'action_links']  );

        //build link in settings menu and create front-page plugin
        add_action('admin_init', [Settings::class, 'settings_init']);
        add_action('admin_menu', [Settings::class, 'settings_menu']);

        //after user (un)accepts terms use of plugin
        add_filter('admin_post_wpr_enable_backups', [Settings::class, 'access']);
        add_filter('admin_post_wpr_enable_backups_ref', [Settings::class, 'access_ref']);
        //after user unaccepts terms or deactivates plugin remove access to db
        register_deactivation_hook( __FILE__,  [Settings::class, 'deactivate'] );

        //after user (un)accepts email backups
        add_filter('admin_post_email_backups', [General::class, 'save_email_backups']);

        //after user accepts deletes all backups
        add_filter('admin_post_delete_backups', [Backup::class, 'delete_all']);

        //when wp-repair has an action: token, daily backup, etc
        add_action('init', [General::class, 'wprepair_actions']);
    }

    //GENERAL
    //creates links in the plugins overview
    public function action_links( $links )
    {
        array_unshift($links , '<a href="https://wp.repair/membership-login/" target="_blank">' . esc_html__( 'Login Account' ) . '</a>');
        array_unshift($links , '<a href="'. esc_url( get_admin_url(null, 'tools.php?page=wp-repair') ) .'">' . esc_html__( 'Settings' ) . '</a>');
        return $links;
    }
}

include __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/includes/settings.php';
include __DIR__ . '/includes/general.php';
include __DIR__ . '/includes/backup.php';
include __DIR__ . '/includes/wpraccess.php';
include __DIR__ . '/includes/exec.php';

new WPREPAIR();
