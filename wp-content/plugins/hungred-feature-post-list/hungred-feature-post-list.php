<?php 
/*
Plugin Name: Hungred Feature Post List
Plugin URI: http://hungred.com/useful-information/wordpress-plugin-hungred-feature-post-list/
Description: This plugin is design for hungred.com and people who face the same problem! Please visit the plugin page for more information.
Author: Clay lua
Version: 1.1.0
Author URI: http://hungred.com
*/

/*  Copyright 2009  Clay Lua  (email : clay@hungred.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once("hungred.php");
$hungredObj = new Hungred_Tools();
add_action('wp_dashboard_setup', array($hungredObj,'widget_setup'));	
/*
Structure of the plugin
*/
/*
Name: add_hfpl_to_admin_panel_actions
Usage: use to add an options on the Setting section of Wordpress
Parameter: 	NONE
Description: this method depend on hfpl_admin for the interface to be produce when the option is created
			 on the Setting section of Wordpress
*/

function add_hfpl_to_admin_panel_actions() {
    $plugin_page = add_options_page("Hungred Feature Post List", "Hungred Feature Post List", 10, "Hungred Feature Post List", "hfpl_admin");  
	add_action( 'admin_head-'. $plugin_page, 'hfpl_admin_header' );

}

/*
Name: hfpl_admin_header
Usage: stop hfpl admin page from caching
Parameter: 	NONE
Description: this method is to stop hfpl admin page from caching so that the preview is shown.
*/
function hfpl_admin_header()
{
nocache_headers();
}
/*
Name: hfpl_admin
Usage: provide the GUI of the admin page
Parameter: 	NONE
Description: this method depend on hfpl_admin_page.php to display all the relevant information on our admin page
*/
function hfpl_admin(){
	global $hungredObj;
	$support_links = "";
	$plugin_links = array();
	$plugin_links["url"] = "http://hungred.com/useful-information/wordpress-plugin-hungred-feature-post-list/";
	$plugin_links["wordpress"] = "hungred-hungred-feature-post-list";
	$plugin_links["development"] = "https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=i_ah_yong%40hotmail%2ecom&lc=MY&item_name=Support%20Hungred%20Post%20Thumbnail%20Development&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest";
	$plugin_links["donation"] = "https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=i_ah_yong%40hotmail%2ecom&lc=MY&item_name=Coffee&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted";
	$plugin_links["pledge"] = "<a href='http://www.pledgie.com/campaigns/6187'><img alt='Click here to lend your support to: Hungred Wordpress Development and make a donation at www.pledgie.com !' src='http://www.pledgie.com/campaigns/6187.png?skin_name=chrome' border='0' /></a>";
	$support_links = "http://wordpress.org/tags/hungred-hungred-feature-post-list";
	require_once('hfpl_admin_page.php'); 
	?>
	<div class="postbox-container" id="hungred_sidebar" style="width:20%;">
		<div class="metabox-holder">	
			<div class="meta-box-sortables">
				<?php
					$hungredObj->news(); 
					$hungredObj->plugin_like($plugin_links);
					$hungredObj->plugin_support($support_links);
				?>
			</div>
			<br/><br/><br/>
		</div>
	</div>
	<?php
	 
}
add_action('admin_menu', 'add_hfpl_to_admin_panel_actions');
/*
Name: hfpl_post_option
Usage: add a container into Wordpress post section
Parameter: 	NONE
Description: this method adds a container to Wordpress post section for user to upload images for their thumbnail.
			 This method depends on hfpl_post_display for GUI.
*/
function hfpl_post_option()
{
	add_meta_box( "hfpl_box", "Hungred Feature Post List Options", "hfpl_post_display", 'post', "side", "low" );	
}
/*
Name: hfpl_post_option
Usage: include the file and print them out to the container provided by Wordpress on the post section
Parameter: 	NONE
Description: This method depends on hfpl_post_page.php for the code.
*/
function hfpl_post_display()
{
	require_once(WP_PLUGIN_DIR .'/hungred-feature-post-list/hfpl_post_page.php');
}
/*
Name: hfpl_loadcss
Usage: load the relevant CSS external files into Wordpress post section
Parameter: 	NONE
Description: uses wp_enqueue_style for safe printing of CSS style sheets
*/
function hfpl_loadcss()
{
	wp_enqueue_style('hfpl_ini',WP_PLUGIN_URL.'/hungred-feature-post-list/css/hfpl_ini.css');
}
/*
Name: hfpl_loadjs
Usage: load the relevant JavaScript external files into Wordpress post section
Parameter: 	NONE
Description: uses wp_enqueue_script for safe printing of JavaScript
*/
function hfpl_loadjs()
{
	wp_enqueue_script('jquery');
	wp_enqueue_script('hfpl_ini', WP_PLUGIN_URL.'/hungred-feature-post-list/js/hfpl_ini.js');
}
add_action('admin_print_scripts', 'hfpl_loadjs');
add_action('admin_print_styles', 'hfpl_loadcss');
add_action('admin_menu', 'hfpl_post_option');
function hfpl_id()
{
	echo "
	<!-- This site is power up by Hungred Feature Post List -->
	";
}
add_action('wp_head', 'hfpl_id');
/*
Name: hfpl_install
Usage: upload all the table required by this plugin upon activation for the first time
Parameter: 	NONE
Description: the structure of our Wordpress plugin
*/
function hfpl_install()
{

    global $wpdb;
	$table = $wpdb->prefix."hfpl_records";
    $structure = "CREATE TABLE IF NOT EXISTS `".$table."` (
        hfpl_post_id Double NOT NULL DEFAULT 0,
		hfpl_status varchar(1) NOT NULL DEFAULT '0',
		UNIQUE KEY id (hfpl_post_id)
    );";
    $wpdb->query($structure);


    $table = $wpdb->prefix."hfpl_options";
    $structure = "CREATE TABLE IF NOT EXISTS `".$table."` (
		hfpl_option_id DOUBLE NOT NULL AUTO_INCREMENT ,
        hfpl_no_post Double NOT NULL DEFAULT 5,
		hfpl_type varchar(1) NOT NULL DEFAULT 'B',
		hfpl_header varchar(255) NOT NULL DEFAULT 'Feature Posts',
		hfpl_header_class varchar(255) NOT NULL DEFAULT 'widgettitle',
		hfpl_widget_class varchar(255) NOT NULL,
		UNIQUE KEY id (hfpl_option_id)
    );";
    $wpdb->query($structure);
	$wpdb->query("INSERT INTO $table(hfpl_option_id)
	VALUES('1')");
}
if ( function_exists('register_activation_hook') )
	register_activation_hook('hungred-feature-post-list/hungred-feature-post-list.php', 'hfpl_install');
	
/*
Name: hfpl_uninstall
Usage: delete hfpl table
Parameter: 	NONE
Description: the structure of our Wordpress plugin
*/
function hfpl_uninstall()
{
	global $wpdb;

	$table = $wpdb->prefix."hfpl_records";
	$structure = "DROP TABLE `".$table."`";
	$wpdb->query($structure);
	
	$table = $wpdb->prefix."hfpl_options";
	$structure = "DROP TABLE `".$table."`";
	$wpdb->query($structure);
}
if ( function_exists('register_uninstall_hook') )
    register_uninstall_hook(__FILE__, 'hfpl_uninstall');
	

	
function hfpl_post_delete()
{
	global $post;
	global $wpdb;
	$deletedID = $post->ID;
	$table = $wpdb->prefix."hfpl_records";
	$sqlquery = "DELETE FROM `".$table."`
				WHERE `hfpl_post_id` = '".$deletedID."'";
	$wpdb->query($sqlquery);	
}	
add_action('delete_post', 'hfpl_post_delete');
function hfpl_control()
{
	echo "Just add this into the widget siderbar and the plugin will be automatically activated!";
}

function hfpl_widget($args)
{
	global $wpdb;
	$table = $wpdb->prefix."hfpl_options";
	$query = "SELECT * FROM `".$table."` WHERE 1 AND `hfpl_option_id` = '1' limit 1";
	$options = $wpdb->get_row($query,ARRAY_A);
	
	$table = $wpdb->prefix."hfpl_records";
	$query = "SELECT * FROM `".$table."` WHERE 1 AND `hfpl_status` = 't'";
	$row = $wpdb->get_results($query);
	
	$feature_post = Array();
	if($options['hfpl_type'] == 'B' || $options['hfpl_type'] == 'S')
		foreach ($row as $post) {
			
			$feature_post[] = $post->hfpl_post_id;
			if(count($feature_post) >= $options['hfpl_no_post'])
				break;
		}
	if($options['hfpl_type'] == 'B' || $options['hfpl_type'] == 'R')
	if(count($feature_post) < $options['hfpl_no_post'])
	{
		$shortage = $options['hfpl_no_post']- count($feature_post);
		$rand_posts = get_posts('orderby=rand');
		foreach( $rand_posts as $post ) :
			if(!in_array($post->ID, $feature_post))
				$feature_post[] = $post->ID;
			if(count($feature_post) >= $options['hfpl_no_post'])
				break;
		endforeach;
	}
	

	extract($args); 
	echo $before_widget;
	echo $before_title.$options['hfpl_header'].$after_title;
	echo '<ul>';
	$i = 0;
	foreach($feature_post as $postid)
	{
		if($i< $options['hfpl_no_post'])
		{
			$post = get_post($postid, OBJECT);
			echo '<li><a href="'.get_permalink($post->ID).'">'.$post->post_title.'</a></li>';
		}
		else
			break;
	}
	echo '</ul>'. $after_widget;

}


function hfpl_register()
{
    register_sidebar_widget('Hungred Feature Post List', 'hfpl_widget');
    register_widget_control('Hungred Feature Post List', 'hfpl_control');
}
add_action("widgets_init",'hfpl_register');

?>