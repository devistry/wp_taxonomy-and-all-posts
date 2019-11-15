<?php
/**
 * @package Taxonomy and All Posts
 */
/*
Plugin Name: Taxnomy and All Posts
Plugin URI: http://www.devistry.com
Description: List all Taxonomy and drill down to see related posts by Post Type
Version: 1.0
Author: Deveyesh Mistry
Author URI: http://www.devistry.com
License: GPLv2 or later

----
Changes:

2019 ::::::::::::

Nov 15 - Removed nav_menu from Taxonomy list

Nov 14 - Initial Release

*/

define( 'TAAP_FILEPATH', plugin_dir_path( __FILE__ ) );
/*-----------------------------------------------------------------------------------*/
/*  Custom Taxonomies
/*-----------------------------------------------------------------------------------*/
require_once TAAP_FILEPATH . '/functions/taxonomy-browse-all-posts.php';
