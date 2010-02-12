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
global $wpdb, $mode;
$error = "";
$table = $wpdb->prefix."hfpl_options";

if($_POST['hfpl_no_post'] != "")
{
//update the database with Replace instead of insert to avoid duplication data in the table
	$query = "REPLACE INTO $table(hfpl_option_id, hfpl_type, hfpl_no_post,hfpl_header,hfpl_header_class,hfpl_widget_class) 
	VALUES('1', '".$_POST['hfpl_type']."', '".$_POST['hfpl_no_post']."', '".$_POST['hfpl_header']."', '".$_POST['hfpl_header_class']."', '".$_POST['hfpl_widget_class']."')";
	$wpdb->query($query);

}

//retrieve new data
$query = "SELECT * FROM `".$table."` WHERE 1 AND `hfpl_option_id` = '1' limit 1";
$row = $wpdb->get_row($query,ARRAY_A);


?>
<div class="hfpl_wrap">
	<div class="wrap">
	<?php    echo "<h2>" . __( 'Hungred Feature Post List Configuration' ) . "</h2>"; ?>
	</div>
	<form name="hfpl_form" id="hfpl_form" class="hfpl_admin" onsubmit="return validate()" enctype="multipart/form-data" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
	<div class="postbox-container" id="hfpl_admin">
		<div class="metabox-holder">		
			<div class="meta-box-sortables ui-sortable" >
				<div class='postbox'>		
					<?php    echo "<h3  class='hndle'>" . __( 'Feature Settings' ) . "</h3>"; ?>
					<div class='inside size'>
					<p><div class='label'><?php _e("Feature Header" ); ?></div><input type="text" id="hfpl_header" name="hfpl_header" value="<?php echo $row['hfpl_header']; ?>" size="20"></p>
					<p><div class='label'><?php _e("Feature Number" ); ?></div><input type="text" id="hfpl_no_post" name="hfpl_no_post" value="<?php echo $row['hfpl_no_post']; ?>" size="20"></p>
					<p><div class='label'><?php _e("Feature Type: " ); ?>
					</div><SELECT name="hfpl_type">
					<?php 
					if($row['hfpl_type'] == "S"){ ?>
					<option selected value="S">Selected Only</option>
					<option value="R">Random Only</option>
					<option value="B">Both</option>
					<?php }else if($row['hfpl_type'] == "R"){?>
					<option value="S">Selected Only</option>
					<option selected value="R">Random Only</option>
					<option value="B">Both</option>
					<?php }else if($row['hfpl_type'] == "B"){?>
					<option value="S">Selected Only</option>
					<option value="R">Random Only</option>
					<option selected value="B">Both</option>
					<?php }?>
					</SELECT>
					</p>
					<p class="submit">
						<input type="submit" id="submit" value="<?php _e('Update Options' ) ?>" />
					</p>
					</div>
				</div>
				<div class='postbox'>		
					<?php    echo "<h3  class='hndle'>" . __( 'Selected Feature Post' ) . "</h3>"; ?>
					<table class="widefat post fixed" cellspacing="0">
						<thead>
						<tr>
					<?php print_column_headers('edit'); ?>
						</tr>
						</thead>

						<tfoot>
						<tr>
					<?php print_column_headers('edit', false); ?>
						</tr>
						</tfoot>

						<tbody>
					<?php 
					$table = $wpdb->prefix."hfpl_records";
					$query = "SELECT * FROM `".$table."` WHERE 1 AND `hfpl_status` = 't'";
					$row = $wpdb->get_results($query);
					foreach ($row as $post) {
						$detail = get_post($post->hfpl_post_id, OBJECT);
						_post_row($detail, $comment_pending_count[$post->hfpl_post_id], $mode);
					}

					?>
						</tbody>
					</table>
				</div>


				
					<?php if($error != ""){?>
					<div class='postbox'>	
						<?php    echo "<h3  class='hndle'>" . __( 'Feature Error Section' ) . "</h3>"; ?>
						<div class='inside size'>
							<p><div class='label'>
							<h2><?php _e("Error Message: " ); ?></h2>
							</div>
							<div class="hfpl_red">
							<?php echo $error; ?>
							</div>
							</p>
						</div>
					</div>
					<?php }?>
			</div>
		</div>
	</div>
	</form>
</div>
<script type="text/javascript">
/*
Name: validate
Usage: use to validate the form upon user submission
Parameter: 	NONE
Description: use to validate all the basic inputs by the users
*/
function validate()
{
	var number = document.getElementById('hfpl_no_post');
	var height = document.getElementById('hfpl_height');

	if(isNumeric(number, "Invalid number found in feature number"))
	{
		return true;
	}
						
					
	return false;
	
}
/*
Name: isNumeric
Usage: use to validate width, height, space and gap text box
Parameter: 	elem: the DOM object of each element
			helperMsg: the pop out box message
Description: This is a simple method to check whether a given text box string contains 
			 numbers and '.' symbols
*/
function isNumeric(elem, helperMsg){
	var numericExpression = /^[0-9.]+$/;
	if(elem.value.match(numericExpression)){
		return true;
	}else{
		alert(helperMsg);
		elem.focus();
		return false;
	}
}
</script>
