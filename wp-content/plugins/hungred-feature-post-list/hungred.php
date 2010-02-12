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

if (!class_exists('Hungred_Tools')) {
	class Hungred_Tools {

		function Hungred_Tools() {	
			add_action('wp_dashboard_setup', array(&$this,'widget_setup'));	
		}
		

		function postbox($id, $title, $content) {
		?>
			<div id="<?php echo $id; ?>" class="postbox">
				<div class="handlediv" title="Click to toggle"><br /></div>
				<h3 class="hndle"><span><?php echo $title; ?></span></h3>
				<div class="inside">
					<?php echo $content; ?>
				</div>
			</div>
		<?php
		}	

		function plugin_like($link) {
			$content = '<p>'.__('Why not do any or all of the following:','hungredplugin').'</p>';
			$content .= '<ul class="hungred_list">';
			$content .= '<li><a href="'.$link["url"].'" target="_blank">'.__('Link or help us visit our sponsors.','hungredplugin').'</a></li>';
			$content .= '<li><a href="http://wordpress.org/extend/plugins/'.$link["wordpress"].'/" target="_blank">'.__('Give it a good rating on WordPress.org.','hungredplugin').'</a></li>';
			$content .= '<li><a href="'.$link["development"].'" target="_blank">'.__('Contribute to hungred development.','hungredplugin').'</a></li>';
			$content .= '<li><a href="'.$link["donation"].'" target="_blank">'.__('Donate a token of your appreciation.','hungredplugin').'</a></li>';
			$content .= $link["pledge"];
			$content .= '</ul>';
			$this->postbox($this->hook.'like', 'Like this plugin?', $content);
		}	

		function plugin_support($url) {
			$content = '<p class="hungred_list">'.__('If you have any problems with this plugin or good ideas for improvements or new features, please talk about them in the','hungredplugin').' <a href="'.$url.'">'.__("Support forums",'hungredplugin').'</a>.</p>';
			$this->postbox($this->hook.'support', 'Need support?', $content);
		}

		function news() {
			require_once(ABSPATH.WPINC.'/rss.php');  
			if ( $rss = fetch_rss( 'http://hungred.com/feed/' ) ) {
				$content = '<ul class="hungred_list">';
				$rss->items = array_slice( $rss->items, 0, 3 );
				foreach ( (array) $rss->items as $item ) {
					$content .= '<li class="hungred">';
					$content .= '<a class="rsswidget" href="'.clean_url( $item['link'], $protocolls=null, 'display' ).'">'. htmlentities($item['title']) .'</a> ';
					$content .= '</li>';
				}
				$content .= '<li class="rss"><a href="http://hungred.com/feed/">Subscribe to RSS</a></li>';
				//$content .= '<li class="email"><a href="http://hungred.com/">Subscribe by email</a></li>';
				$content .= '</ul>';
				$this->postbox('hungredplugin', 'Latest news from Hungred Dot Com', $content);
			} else {
				$this->postbox('hungredplugin', 'Latest news from Hungred Dot Com', 'No news at the moment.');
			}
		}
		
		function text_limit( $text, $limit, $finish = ' [&hellip;]') {
			$text =strip_tags($text);
			if( strlen( $text ) > $limit ) {
		    	$text = substr( $text, 0, $limit );
				$text = substr( $text, 0, - ( strlen( strrchr( $text,' ') ) ) );
				$text .= $finish;
			}
			return $text;
		}
		function db_widget() {
			$options = get_option('hungrednewswidget');
			if (isset($_POST['hungred_removedbwidget'])) {
				$options['removedbwidget'] = true;
				update_option('hungrednewswidget',$options);
			}			
			if ($options['removedbwidget']) {
				echo "If you reload, this widget will be gone and never appear again, unless you decide to delete the database option 'hungrednewswidget'.";
				return;
			}
			require_once(ABSPATH.WPINC.'/rss.php');
			if ( $rss = fetch_rss( 'http://hungred.com/feed/' ) ) {
				echo '<div class="rss-widget">';
				echo '<a href="http://hungred.com/" title="Go to hungred.com"><img src="http://img9.yfrog.com/img9/3575/hungredlogoj.jpg" class="alignright" alt="hungred"/></a>';			
				echo '<ul>';
				$rss->items = array_slice( $rss->items, 0, 3 );
				foreach ( (array) $rss->items as $item ) {
					echo '<li>';
					echo '<a class="rsswidget" href="'.clean_url( $item['link'], $protocolls=null, 'display' ).'">'. htmlentities($item['title']) .'</a> ';
					echo '<span class="rss-date">'. date('F j, Y', strtotime($item['pubdate'])) .'</span>';
					echo '<div class="rssSummary">'. $this->text_limit($item['description'],200) .'</div>';
					echo '</li>';
				}
				echo '</ul>';
				echo '<div style="border-top: 1px solid #ddd; padding-top: 10px; text-align:center;">';
				echo '<a href="http://hungred.com/feed/"><img src="'.get_bloginfo('url').'/wp-includes/images/rss.png" alt=""/> Subscribe to RSS</a>';
				echo ' &nbsp; &nbsp; &nbsp; ';
				//echo '<a href="http://hungred.com/email-blog-updates/"><img src="http://img9.yfrog.com/img9/3575/hungredlogoj.jpg" alt=""/> Subscribe by email</a>';
				//echo '<form class="alignright" method="post"><input type="hidden" name="hungred_removedbwidget" value="true"/><input title="Remove this widget from all users dashboards" type="submit" value="X"/></form>';
				echo '</div>';
				echo '</div>';
			}
		}

		function widget_setup() {
			$options = get_option('hungrednewswidget');
			if (!$options['removedbwidget'])
		    	wp_add_dashboard_widget( 'hungred_db_widget' , 'Hungred Dot Com News' , array(&$this, 'db_widget'));
		}
	}
}

?>