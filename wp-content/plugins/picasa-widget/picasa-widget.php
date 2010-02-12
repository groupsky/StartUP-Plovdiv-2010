<?php
/*
Plugin Name: Picasa Widget
Author URI: http://familia.capan.ro
Plugin URI: http://www.cnet.ro/wordpress/picasa-widget
Description: Show Picasa pictures in sidebar
Author: Radu Capan
Version: 1.2

CHANGELOG
See readme.txt
*/
 
load_plugin_textdomain( 'picasawidget', '/wp-content/plugins/picasa-widget' );

function picasa_widget_square($dim){
	return ($dim ==32) || ($dim ==48) || ($dim ==64) || ($dim ==82) || ($dim ==160);
}

function picasa_widget_albums(){
	$options = get_option('widget_PicasaSidebar');
	$varianta = $options['mode'];
	$items = $options['items'];
	$space= $options['space'];
	$border= $options['border'];
	$bcolor = $options['bcolor'];
	$catecoloane = $options['cols'];
	if($varianta == 3){
		$maxres = $options['lalb'];
		$aleator = rand(0,$maxres-1);
	}
	else
		$maxres = $options['items'];
	if(file_exists(ABSPATH . WPINC . '/rss.php') )
		require_once(ABSPATH . WPINC . '/rss.php');
	else
		require_once(ABSPATH . WPINC . '/rss-functions.php');
	$result = "";
	if($varianta == 1)
		$result = "<p><ul>";
	else
		$result = "<p align=center>";
	$rss = fetch_rss("http://picasaweb.google.com/data/feed/base/user/".$options['username']."?kind=album&alt=rss&hl=en_US&access=public&max-results=".$maxres);
	if (is_array($rss->items)) {
		$i = 0;
		foreach($rss->items as $item) {
			$titlu = $item['title'];
			$titlu = str_replace("'","",$titlu);
			$titlu = str_replace('"',"",$titlu);
			$link = $item['link'];
			if($varianta==1)
				$result .= "<li><a href=".$item['link']." target=_blank>".$titlu."</a></li><p>";
			
			if (count($albums)!=0 && !in_array($item['title'],$albums))
				continue;
			preg_match('/.*src="(.*?)".*/',$item['description'],$sub);
			$path = $sub[1];
			$path = str_replace("s160-","s".$options['width']."-",$path);
			if($varianta==2){
				$result .= "<a href=".$link." target=_blank><img src=".$path." class=picasa-widget-img hspace=".$space." vspace=".$space." style='border:".$border."px ".($bcolor==""?"":"solid ".$bcolor)."' alt='$titlu' title='$titlu'></a>";
				if($catecoloane!=1 )
				if(($i+1) % $catecoloane==0)
						$result .= "<br>";
					else
						$result .= "";
				else
					$result .= "<br>";
			}
			if($varianta==3){
				if($i==$aleator){
					if($options['stitle'])
						$result .= "".$titlu."<br>";
					$rss2 = fetch_rss(str_replace("entry","feed",$item['guid'])."&kind=photo");
					if (!$rss2)
						continue;
					$j=0;
					foreach($rss2->items as $item2) {
						$titlu = $item2['title'];
						$titlu = str_replace("'","",$titlu);
						$titlu = str_replace('"',"",$titlu);
						preg_match('/.*src="(.*?)".*/',$item2['description'],$sub);
						$path = $sub[1];
						$path = str_replace("s288","s".$options['width'].(picasa_widget_square($options['width'])?"-c":""),$path);
						$pozele[$j++] = "<a href=".$item2['link']." target=_blank><img src=".$path." class=picasa-widget-img hspace=".$space." vspace=".$space." style='border:".$border."px ".($bcolor==""?"":"solid ".$bcolor)."' alt='$titlu' title='$titlu'></a>";
					}
					srand((float)microtime() * 1000000);
					shuffle($pozele);
					for($k=0;$k<$items;$k++){
						$result .= $pozele[$k];
						if($items!=1 )
							if(($k+1) % $catecoloane==0)
								$result .= "<br>";
							else
								$result .= "";
						else
							$result .= "<br>";
					}
				}
			}
			$i++;
		}
	}
	if($varianta == 1)
		$result .= "</ul>";
	echo "</p>".$result;
}

function picasa_widget($args) {
	$options = get_option('widget_PicasaSidebar');
	$title = $options['title'];
    extract($args);
	echo $before_widget;
	echo $before_title . ($title==""?__('Latest photos','picasawidget'):$title) . $after_title;
	picasa_widget_albums();
	echo $after_widget; 
}

function picasa_widget_control() {
	$options = $newoptions = get_option('widget_PicasaSidebar');
	if ( $_POST["PicasaSidebar-submit"] ) {
		$newoptions['title'] = trim(strip_tags(stripslashes($_POST["PicasaSidebar-title"])));
		$newoptions['username'] = strip_tags(stripslashes($_POST["PicasaSidebar-username"]));
		$newoptions['mode'] = trim(strip_tags(stripslashes($_POST["PicasaSidebar-mode"])));
		$newoptions['items'] = trim(strip_tags(stripslashes($_POST["PicasaSidebar-items"])));
		$newoptions['cols'] = trim(strip_tags(stripslashes($_POST["PicasaSidebar-cols"])));
		$newoptions['width'] = trim(strip_tags(stripslashes($_POST["PicasaSidebar-width"])));
		$newoptions['lalb'] = trim(strip_tags(stripslashes($_POST["PicasaSidebar-lalb"])));
		$newoptions['space'] = trim(strip_tags(stripslashes($_POST["PicasaSidebar-space"])));
		$newoptions['border'] = trim(strip_tags(stripslashes($_POST["PicasaSidebar-border"])));
		$newoptions['bcolor'] = trim(strip_tags(stripslashes($_POST["PicasaSidebar-bcolor"])));
		$newoptions['stitle'] = isset($_POST["PicasaSidebar-stitle"]);
	}
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option('widget_PicasaSidebar', $options);
	}
	$title = htmlspecialchars($options['title'], ENT_QUOTES);
	$username = htmlspecialchars($options['username'], ENT_QUOTES);
	$mode = htmlspecialchars($options['mode'], ENT_QUOTES);
	$items = htmlspecialchars($options['items'], ENT_QUOTES);
	$cols = htmlspecialchars($options['cols'], ENT_QUOTES);
	$width = htmlspecialchars($options['width'], ENT_QUOTES);
	$lalb = htmlspecialchars($options['lalb'], ENT_QUOTES);
	$space = htmlspecialchars($options['space'], ENT_QUOTES);
	$border = htmlspecialchars($options['border'], ENT_QUOTES);
	$bcolor = htmlspecialchars($options['bcolor'], ENT_QUOTES);
	$stitle = $options['stitle'] ? 'checked="checked"' : '';
	$e=($username=="");
	?>
	<p><label for="PicasaSidebar-title"><?php _e('Widget Title','picasawidget');?>:</label><br>
	<input class="widefat" id="PicasaSidebar-title" name="PicasaSidebar-title" type="text" value="<?php echo ($e?__("Photos","picasawidget"):$title); ?>" /></p>
	<p><label for="PicasaSidebar-feeds"><?php _e('Picasa username','picasawidget');?>:</label><br>
	<input class="widefat" id="PicasaSidebar-username" name="PicasaSidebar-username" value="<?php echo ($e?__("Your Picasa username","picasawidget"):$username); ?>" /></p>
	<p><label for="PicasaSidebar-mode"><?php _e('Widget Mode','picasawidget');?>:</label><br>
	<select id="id="PicasaSidebar-mode" name="PicasaSidebar-mode" >
	<option value="1" <?php echo (($mode == '1') ? 'selected' : ''); ?> ><?php _e('1. Latest albums (text)','picasawidget');?></option>
	<option value="2" <?php echo (($mode == '2') ? 'selected' : ''); echo ($e?"selected":""); ?>><?php _e('2. Latest albums (thumbnails)','picasawidget');?></option>
	<option value="3" <?php echo (($mode == '3') ? 'selected' : ''); ?>><?php _e('3. Random pictures','picasawidget');?></option>
	</select></p>		
	<p><label for="PicasaSidebar-number"><?php _e('Number of items','picasawidget');?>:<br><small><?php _e('albums or pictures, based on widget mode','picasawidget');?></small> </label><br>
	<input class="widefat" id="PicasaSidebar-items" name="PicasaSidebar-items" type="text" value="<?php echo ($e?4:$items); ?>" /></p>	
	<p><label for="PicasaSidebar-width"><?php _e('Width of the thumbnails','picasawidget');?>:<br><small><?php _e('albums or pictures, based on widget mode','picasawidget'); echo'<br>* ';_e('for squared thumbnails','picasawidget'); echo '<br>'; _e('choose only squared thumbnail for mode 2','picasawidget');?></small></label><br>
	<select id="PicasaSidebar-width" name="PicasaSidebar-width">
	<option value="32" <?php echo (($width == '32') ? 'selected' : ''); ?>>32 *</option>
	<option value="48" <?php echo (($width == '48') ? 'selected' : ''); ?>>48 *</option>
	<option value="64" <?php echo (($width == '64') ? 'selected' : ''); ?>>64 *</option>
	<option value="82" <?php echo (($width == '82') ? 'selected' : ''); ?>>82 *</option>
	<option value="144" <?php echo (($width == '144') ? 'selected' : ''); ?>>144</option>
	<option value="160" <?php echo (($width == '160') ? 'selected' : ''); echo ($e?"selected":""); ?>>160 *</option>
	<option value="200" <?php echo (($width == '200') ? 'selected' : ''); ?>>200</option>
	<option value="288" <?php echo (($width == '288') ? 'selected' : ''); ?>>288</option>
	<option value="400" <?php echo (($width == '400') ? 'selected' : ''); ?>>400</option>
	</select></p>		
	<p><small><?php _e('For mode 2 and 3','picasawidget');?></small><br><label for="PicasaSidebar-cols"><?php _e('Number of columns','picasawidget');?>: </label>
	<input class="widefat" id="PicasaSidebar-cols" name="PicasaSidebar-cols" type="text" value="<?php echo ($e?1:$cols); ?>" /></p>
	<p><small><?php _e('For mode 3','picasawidget');?></small><br><label for="PicasaSidebar-cols"><?php _e('Random from last <em>n</em> albums','picasawidget');?>: </label><br><small><?php _e('use 1 to limit to the last album','picasawidget');?></small>
	<input class="widefat" id="PicasaSidebar-lalb" name="PicasaSidebar-lalb" type="text" value="<?php echo ($e?1:$lalb); ?>" /></p>
	<p><label for="PicasaSidebar-stitle"><?php _e('Show album title?','picasawidget');?> <input class="checkbox" type="checkbox" <?php echo $stitle; ?> id="PicasaSidebar-stitle" name="PicasaSidebar-stitle" /></label></p>
	<p><small><?php _e('Make thumbnails looks nice','picasawidget');?></small><br><label for="PicasaSidebar-space"><?php _e('H-space and V-space','picasawidget');?>: </label>
	<input class="widefat" id="PicasaSidebar-space" name="PicasaSidebar-space" type="text" value="<?php echo ($e?0:$space); ?>" /></p>
	<p><label for="PicasaSidebar-border"><?php _e('Thickness of the border','picasawidget');?>: </label>
	<input class="widefat" id="PicasaSidebar-border" name="PicasaSidebar-border" type="text" value="<?php echo ($e?0:$border); ?>" /></p>
	<p><label for="PicasaSidebar-bcolor"><?php _e('Hexa (eg: #cc0000) color of the border','picasawidget');?>: </label>
	<input class="widefat" id="PicasaSidebar-bcolor" name="PicasaSidebar-bcolor" type="text" value="<?php echo ($e?"":$bcolor); ?>" /></p>
	<p><label for="PicasaSidebar-help"><?php _e('For help, go','picasawidget'); echo ' <a href=http://www.cnet.ro/wordpress/picasa-widget target=_blank>'; _e('here','picasawidget'); echo '</a>';?></label></p>
	<input type="hidden" id="PicasaSidebar-submit" name="PicasaSidebar-submit" value="1" />
	<?php
}


function init_picasa_widget(){
	register_sidebar_widget("Picasa Widget", "picasa_widget");
	register_widget_control("Picasa Widget", "picasa_widget_control");
}
add_action("plugins_loaded", "init_picasa_widget");

?>