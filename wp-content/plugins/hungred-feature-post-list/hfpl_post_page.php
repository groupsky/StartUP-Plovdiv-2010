<?php
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
global $post;
global $wpdb;
$hfpl_post = $post->ID;

$hfpl_status = $post->post_status;
$hfpl_table = $wpdb->prefix."hfpl_records";
$hfpl_query = "SELECT `hfpl_post_id` FROM `".$hfpl_table."` WHERE `hfpl_post_id` = '".$hfpl_post."' AND `hfpl_status` = 't' limit 1";
$hfpl_row = $wpdb->get_row($hfpl_query,ARRAY_A);
$hfpl_hasID = $hfpl_row['hfpl_post_id'];
?>
<div id="hfpl_main">
		<?php 
		if($hfpl_hasID != ""){
			echo "<input type='checkbox' name='hfpl_checkbox' id='hfpl_checkbox' checked />";
		}else{
			echo "<input type='checkbox' name='hfpl_checkbox' id='hfpl_checkbox' />";
		} _e(" Feature This Post?");
		echo "<p><a href='".get_settings('siteurl')."/wp-admin/options-general.php?page=Hungred Feature Post List'>Return to plugin admin page</a></p>";
		echo "<input type='hidden' name='hfpl_id' id='hfpl_id' value='".$hfpl_post."' />";
		echo "<input type='hidden' name='hfpl_status' id='hfpl_status' value='".$hfpl_status."' />";
		?>
</div>