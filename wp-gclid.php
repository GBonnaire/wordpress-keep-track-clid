<?php
/**
 * Keep Track CLID.
 *
 * @package   KeepTrackCLID
 * @copyright Copyright (C) 2022, GBonnaire - contact@gbonnaire.fr
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3 or higher
 *
 * @wordpress-plugin
 * Plugin Name: Keep Track ClickID
 * Version:     1.1.2
 * Plugin URI:  https://github.com/GBonnaire/wordpress-keep-track-clid
 * Description: Keep track GCLID and FBCLID while navigation on your wordpress website
 * Author:      Guillaume Bonnaire
 * Author URI:  https://www.gbonnaire.fr
 * Text Domain: wordpress-keep-track-clid
 * License:     GPL v3
 * Requires at least: 5.9
 * Requires PHP: 5.6.20
 *
 * WC requires at least: 3.0
 * WC tested up to: 6.1
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */




if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! function_exists( 'add_filter' ) ) {
    header( 'Status: 403 Forbidden' );
    header( 'HTTP/1.1 403 Forbidden' );
    exit();
}

$filters = array (
    'post_link',       // when post_type == 'post'
    'page_link',       // when post_type == 'page'
    'attachment_link', // when post_type == 'attachment'
    'post_type_link',  // when post_type is not one of the above
);

foreach ($filters as $filter) {
    add_filter($filter, 'wp_plugin_ktclid_requests_query_args', 10, 3) ;
}

/**
 * Filter WP executed on each interne request
 */
function wp_plugin_ktclid_requests_query_args($permalink)
{    
    if (is_admin()) {
        // Only update link front
        return $permalink;
    }

    if (defined("ELEMENTOR_PLUGIN_BASE") && is_plugin_active( ELEMENTOR_PLUGIN_BASE )) {
        if(\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            return $permalink;
        }
        if( isset( $_GET['elementor-preview'] ) ) {
            return $permalink;
        }
    }



    $params = $_GET;
    if($params == NULL) {
        $params = array();
    }

    if(array_key_exists("gclid", $params) && $params['gclid']!=NULL) {
        wp_plugin_ktclid_session_register("gclid", $params['gclid']);
    } else {
        $params['gclid'] = wp_plugin_ktclid_session_get("gclid");
        if($params['gclid'] == NULL) {
            unset($params['gclid']);
        }
    }

    if(array_key_exists("fbclid", $params) && $params['fbclid']!=NULL) {
        wp_plugin_ktclid_session_register("fbclid", $params['fbclid']);
    } else {
        $params['fbclid'] = wp_plugin_ktclid_session_get("fbclid");
        if($params['fbclid'] == NULL) {
            unset($params['fbclid']);
        }
    }

    return (add_query_arg($params, $permalink)) ;
}

/**
 * On init plugin
 */
function wp_plugin_ktclid_init()
{
    if (is_admin()) {
        // Only update link front
        return ;
    }

    if (defined("ELEMENTOR_PLUGIN_BASE") && is_plugin_active( ELEMENTOR_PLUGIN_BASE )) {
        if(\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            return ;
        }
        if( isset( $_GET['elementor-preview'] ) ) {
            return ;
        }
    }
	
    if($_POST == NULL) {
        $params = $_GET;
        if($params == NULL) {
            $params = array();
        }
        if(array_key_exists("gclid", $params) && $params['gclid']!=NULL) {
            wp_plugin_ktclid_session_register("gclid", $params['gclid']);
        }

        if(array_key_exists("fbclid", $params) && $params['fbclid']!=NULL) {
            wp_plugin_ktclid_session_register("fbclid", $params['fbclid']);
        }

        if(!array_key_exists("gclid", $params) && !array_key_exists("fbclid", $params)) {
            $permalink = get_permalink();
            $params['gclid'] = wp_plugin_ktclid_session_get("gclid");
            $params['fbclid'] = wp_plugin_ktclid_session_get("fbclid");

            if(($params['gclid'] != NULL || $params['fbclid'] != NULL) && wp_redirect(add_query_arg($params, $permalink))) {
                exit;
            }
        }
    }
}

/**
 * register value in session
 * @param string $key
 * @param string $value
 */
function wp_plugin_ktclid_session_register($key, $value) {
    if(session_status() != 2) {
       session_start();
    }
    
    // init package in session
    if(!array_key_exists("wp_plugin_ktclid", $_SESSION)) {
       $_SESSION['wp_plugin_ktclid'] = array();
    }
    
    // Save value in session
    $_SESSION['wp_plugin_ktclid'][$key] = $value;
}

/**
 * get value in session
 * @param string $key
 * @return NULL|string
 */
function wp_plugin_ktclid_session_get($key) {
    if(session_status() != 2) {
       session_start();
    }

    if(array_key_exists("wp_plugin_ktclid", $_SESSION) && array_key_exists($key, $_SESSION['wp_plugin_ktclid'])) {
         return $_SESSION['wp_plugin_ktclid'][$key];
    }
    return NULL;
}

/**
 * Load plugin on WP
 */
add_action("init", "wp_plugin_ktclid_init");