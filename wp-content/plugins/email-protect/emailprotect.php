<?php
/*
Plugin Name: Email Protect
Plugin URI:  http://simply-basic.com/email-protect-plugin
Description: Automatically changes email address is posts, pages, and comments into safe alternatives (such as an image) to prevent spam harvesters from collecting them. You can also use this in your templates by placing <code>&lt;?php ep_email_protect('email@example.com') ?&gt;</code> in your templates. Adjust settings and view "Read Me" under Email Protect in the Options/Settings page
Author: John Kolbert
Version: 1.0.1
Author URI: http://simply-basic.com/

Copyright Notice

Copyright © 2008 by John Kolbert (aka Simply-Basic.com)

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the “Software”), to deal in
the Software without restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the
Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A
PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

function se_option_menu() {  // install the options menu
	if (function_exists('current_user_can')) {
		if (!current_user_can('manage_options')) return;
	} else {
		global $user_level;
		get_currentuserinfo();
		if ($user_level < 8) return;
	}
	if (function_exists('add_options_page')) {
		add_options_page(__('Email Protect'), __('Email Protect'), 1, __FILE__, 'se_options_page');
	}
} 

// Install the options page
add_action('admin_menu', 'se_option_menu');

function se_options_page(){

	global $wpdb;

	
  if (isset($_POST['update_options'])) {
    $options['se_sectype'] = trim($_POST['se_sectype'],'{}');
    $options['se_atchange'] = trim(htmlentities($_POST['se_atchange']),'{}');
    $options['se_dotchange'] = trim($_POST['se_dotchange'],'{}');
	  $options['se_font'] = trim($_POST['se_font'],'{}');
    $options['se_bgcolor'] = trim($_POST['se_bgcolor'],'{}');
    $options['se_ftcolor'] = trim($_POST['se_ftcolor'],'{}');
    $options['se_bdcolor'] = trim($_POST['se_bdcolor'],'{}');
    update_option('se_options_insert', $options);
    
		// Show a message to say we've done something
		echo '<div class="updated"><p>' . __('Options saved') . '</p></div>';
	} else {
		
		$options = get_option('se_options_insert');
	}
	 	 $plugin_path = get_bloginfo('wpurl') . '/wp-content/plugins/' . dirname(plugin_basename(__FILE__)) . '/';
	 	 $test_email = "mytest@example.com";
     $test_email = base64_encode($test_email);
	?>
		<div class="wrap">
		<h2><?php echo __('Email Protect Options'); ?></h2>
    <p>Created by <a href="http://simply-basic.com/">John Kolbert</a><br />
    <a href="#usage">Template Usage</a> - Below<br />
    <a href="http://simply-basic.com/email-protect-plugin/">Offical Readme</a></p>
    
    <div style="border: 1px solid #ddd; padding: 8px; margin: 5px; text-align: center;">
    <p>Based on your settings below, your email will look like this <small>(save settings to refresh)</small>:<br /><br />
   
    <?php $email = get_option('admin_email'); if(function_exists('ep_email_protect')){ ep_email_protect($email); } ?>
     </div>
		<form method="post" action="">
		
		<table class="form-table">
		   <tr valign="top">
		      <th scope="row">Email Security Method:</th>
		      <td><input type="radio" name="se_sectype" id="se_sectype" <?php if ($options['se_sectype'] == "text") {echo "checked";} ?> value="text"  > Text replacement </td>
		      <td><input type="radio" name="se_sectype" id="se_sectype" <?php if ($options['se_sectype'] == "image") {echo "checked";} ?> value="image"  > Image replacement</td>
	     </tr>
	  </table>
	  <h3>Text Replacement Options </h3>
	  	<table class="form-table">
		   <tr valign="top">
		      <th scope="row">Replace "@" with:</th>
          <td><input name="se_atchange" type="text" id="se_atchange" value="<?php echo $options['se_atchange']; ?>" size="5" /> <small>Suggested: [at]</small></td>
          </tr>
          <tr><th>Replace the final period with:</th>
          <td><input name="se_dotchange" type="text" id="se_dotchange" value="<?php echo $options['se_dotchange']; ?>" size="5" /><small>Suggested: [dot]</td>
		      </tr>
		  </table>
		<h3>Image Replacement Options</h3>
  	<table class="form-table">
		   <tr valign="top">
		      <th scope="row">Background Color:<br /> (Hex)</th>
					<td><big>#</big><input name="se_bgcolor" type="text" id="se_bgcolor" value="<?php echo $options['se_bgcolor']; ?>" size="6" /> <small>Enter 3 or 6 digit hex color code. Example: ffffff</small></td>
				</tr>
				<tr valign="top">
		      <th scope="row">Font Color:<br /> (Hex)</th>
					<td><big>#</big><input name="se_ftcolor" type="text" id="se_ftcolor" value="<?php echo $options['se_ftcolor']; ?>" size="6" /> <small>Enter 3 or 6 digit hex color code. Example: ffffff</small></td>
				</tr>
				<tr valign="top">
		      <th scope="row">Border Color<br /> (Hex)</th>
					<td><big>#</big><input name="se_bdcolor" type="text" id="se_bdcolor" value="<?php echo $options['se_bdcolor']; ?>" size="6" /> <small>Enter 3 or 6 digit hex color code. Example: ffffff</small></td>
				</tr>
				</table>
				
				<table class="form-table" style="text-align: center;">
		    <tr >
		      <th scope="row">Email Font</th>
		      <td><input type="radio" name="se_font" id="se_font" <?php if ($options['se_font'] == 1) {echo "checked";} ?> value="1"  > <br /><img src="<?php print "{$plugin_path}image.php?id={$test_email}&font=1&bg=dddddd" ?>" /></td>
		      <td><input type="radio" name="se_font" id="se_font" <?php if ($options['se_font'] == 2) {echo "checked";} ?> value="2"  ><br /> <img src="<?php print "{$plugin_path}image.php?id={$test_email}&font=2&bg=dddddd" ?>" /></td>
		      <td><input type="radio" name="se_font" id="se_font" <?php if ($options['se_font'] == 3) {echo "checked";} ?> value="3"  ><br /> <img src="<?php print "{$plugin_path}image.php?id={$test_email}&font=3&bg=dddddd" ?>" /></td>
          <td><input type="radio" name="se_font" id="se_font" <?php if ($options['se_font'] == 4) {echo "checked";} ?> value="4"  ><br /> <img src="<?php print "{$plugin_path}image.php?id={$test_email}&font=4&bg=dddddd" ?>" /></td>             
          </tr>
        <tr >
		      <td><input type="radio" name="se_font" id="se_font" <?php if ($options['se_font'] == 7) {echo "checked";} ?> value="7"  > <br /><img src="<?php print "{$plugin_path}image.php?id={$test_email}&font=7&bg=dddddd" ?>" /></td>
		      <td><input type="radio" name="se_font" id="se_font" <?php if ($options['se_font'] == 8) {echo "checked";} ?> value="8"  ><br /> <img src="<?php print "{$plugin_path}image.php?id={$test_email}&font=8&bg=dddddd" ?>" /></td>		
		      <td><input type="radio" name="se_font" id="se_font" <?php if ($options['se_font'] == 9) {echo "checked";} ?> value="9"  > <br /><img src="<?php print "{$plugin_path}image.php?id={$test_email}&font=9&bg=dddddd" ?>" /></td>
		      <td><input type="radio" name="se_font" id="se_font" <?php if ($options['se_font'] == 10) {echo "checked";} ?> value="10"  > <br /><img src="<?php print "{$plugin_path}image.php?id={$test_email}&font=10&bg=dddddd" ?>" /></td>
		      <td><input type="radio" name="se_font" id="se_font" <?php if ($options['se_font'] == 11) {echo "checked";} ?> value="11"  > <br /><img src="<?php print "{$plugin_path}image.php?id={$test_email}&font=11&bg=dddddd" ?>" /></td>   
   </table>
		</div>

		<div class="submit"><input type="submit" name="update_options" value="<?php _e('Update') ?>"  style="font-weight:bold;" /> </div>
		</form> 
    	<p>If you've found this free plugin helpful, help support future development by donating any amount securely through PayPal.</p>
    <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_donations">
<input type="hidden" name="business" value="admin@simply-basic.com">
<input type="hidden" name="item_name" value="Support Free Plugins">
<input type="hidden" name="no_shipping" value="0">
<input type="hidden" name="no_note" value="1">
<input type="hidden" name="currency_code" value="USD">
<input type="hidden" name="tax" value="0">
<input type="hidden" name="lc" value="US">
<input type="hidden" name="bn" value="PP-DonationsBF">
<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form> 
<a name="usage"><h2>Usage</h2></a>  		
	<p>Email addresses are automatically replaced by either text or images (depending on your selections) in posts and pages. All email address in comments are obfuscated by text replacement.</p>
    <p>To protect (convert) email addresses elsewhere in your templates, call them using the following code:<br />
    <code>&lt;?php if(function_exists('ep_email_protect')){ep_email_protect($email, $type);} ?&gt;</code><br />
    The email variable holds your email address and "type" can either be <code>text</code> or <code>image</code>. The type is optional. If it is left out the type set in the settings above will be used.</p>
    <p>For example, if your email address is "admin@myexample.com" and you want it converted into an image, enter the following code into your template:
    <code>&lt;?php if(function_exists('ep_email_protect')){ep_email_protect('admin@myexample.com', 'image');} ?&gt;</code></p>
	<?php	
}

function ep_email_protect($email, $type = 'default'){  //used to determine whether to turn the email to obscured text or an image
      $options = get_option('se_options_insert');
      
    if (empty($options['se_sectype']) || (!isset($options['se_sectype']))){
        return $email;
        }
    else{
      
      if((($options['se_sectype'] == "text") && ($type == 'default')) || ($type == "text")){
           print se_email_to_text_only($email);
          }
      if((($options['se_sectype'] == "image") && ($type == 'default')) || ($type == "image")){
          print se_email_to_image($email);
          }
      }
}

function ep_email_change($content){  //used to determine whether to turn the content of posts or comments should be obfuscated
      $options = get_option('se_options_insert');
      
    if (empty($options['se_sectype']) || (!isset($options['se_sectype']))){
        return $content;
        }
    else{
      
      if($options['se_sectype'] == "text"){
           return se_email_to_text_only($content);
          }
      if ($options['se_sectype'] == "image"){
          return se_email_to_image($content);
          }
      }
}

function se_email_to_image($content){  //converts email to image


		$options = get_option('se_options_insert');
    
    if (preg_match('/\W+/', $options['se_font'])){
          $options['se_font'] = 2;
        }
    if (preg_match('/\W+/', $options['se_bgcolor'])) {
          $options['se_bgcolor'] = "000000";
        }
    if (preg_match('/\W+/', $options['se_ftcolor'])) {
          $options['se_ftcolor'] = "ffffff";
        }
    if (preg_match('/\W+/', $options['se_bdcolor'])) {
          $options['se_bdcolor'] = "000000";
        }
        
    $plugin_path = get_bloginfo('wpurl') . '/wp-content/plugins/' . dirname(plugin_basename(__FILE__)) . '/';
  
    preg_match_all('/\b[a-zA-Z0-9._%-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}\b/', $content, $emails, PREG_PATTERN_ORDER); 

     for ($i = 0; $i < count($emails[0]); $i++) {

        $encrypt_email = base64_encode($emails[0][$i]);
        $content = str_replace($emails[0][$i], "<img class=\"posta\" src=\"{$plugin_path}image.php?id={$encrypt_email}&font={$options['se_font']}&bg={$options['se_bgcolor']}&ft={$options['se_ftcolor']}&bd={$options['se_bdcolor']}\" />", $content);
     }
      return $content;
}

function se_email_to_text_only($content){  //used in comments to always turn email to text
	 $options = get_option('se_options_insert');
	 return preg_replace('/([^@\s]+)@([-a-zA-Z0-9\.]+)(\.)([a-zA-Z0-9]{2,})/', '\1 '. $options['se_atchange'] .' \2 '. $options['se_dotchange'] .' \4', $content);
}

add_filter('the_content', 'ep_email_change');
add_filter('comment_text', 'se_email_to_text_only');
add_filter('comment_text_rss', 'se_email_to_text_only');


?>