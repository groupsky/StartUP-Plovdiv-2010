<?php
/*
Plugin Name: Random Posts from Category
Plugin URI: http://sillybean.net/code/wordpress/
Description: A widget that lists random posts from a chosen category.
Version: 1.13
Author: Stephanie Leary
Author URI: http://sillybean.net/
Text Domain: random-posts-from-category 

Changelog:
= 1.13 =
* Fixed a bug where the dropdown would turn into plain text after saving options. (November 18, 2009)
= 1.12 =
* Belorussian (be_BY) translation by <a href="http://fatcow.com">FatCow</a>. (November 15, 2009)
= 1.11 =
* Fixed a bug with the dropdown options (November 14, 2009)
= 1.1 =
* Internationalization improvements (November 13, 2009)\
= 1.0 =
* First release (August 6, 2009)

Copyright 2009  Stephanie Leary  (email : steph@sillybean.net)

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

class RandomPostsFromCategory extends WP_Widget {

	function RandomPostsFromCategory() {
			$widget_ops = array('classname' => 'random_from_cat', 'description' => __( 'random posts from a chosen category', 'random-posts-from-category') );
			$this->WP_Widget('RandomPostsFromCategory', __('Random Posts from Category', 'random-posts-from-category'), $widget_ops);
	}
	
	
	function widget( $args, $instance ) {
			extract( $args );
			
			$title = apply_filters('widget_title', empty( $instance['title'] ) ? __( 'Random Posts' , 'random-posts-from-category') : $instance['title']);
			
			echo $before_widget;
			if ( $title) {
				if ($instance['postlink'] == 1)  {
					$before_title .= '<a href="'.get_category_link($instance['cat']).'">';
					$after_title .= '</a>';
				}
				echo $before_title.$title.$after_title;
			}
			?>
			<ul>
			<?php 
			$random = new WP_Query("cat=".$instance['cat']."&showposts=".$instance['showposts']."&orderby=rand"); 
			// the Loop
			if ($random->have_posts()) : 
			while ($random->have_posts()) : $random->the_post(); /*?>
                <li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                <?php */
				if ($instance['content'] == 'excerpt') {
					if (function_exists('the_excerpt_reloaded')) 
						the_excerpt_reloaded($instance['words'], $instance['tags'], 'content', FALSE, '', '', '1', '');
					else the_excerpt();  // this covers Advanced Excerpt as well as the built-in one
				}
				if ($instance['content'] == 'content') the_content();
			endwhile; endif;
			?>
			</ul>
			<?php
			echo $after_widget;
	}
	
	
	function update( $new_instance, $old_instance ) {
			$instance = $old_instance;
			$instance['title'] = strip_tags($new_instance['title']);
			$instance['cat'] = $new_instance['cat'];
			$instance['showposts'] = $new_instance['showposts'];
			$instance['content'] = $new_instance['content'];
			$instance['postlink'] = $new_instance['postlink'];
			$instance['words'] = $new_instance['words'];
			$instance['tags'] = $new_instance['tags'];
			return $instance;
	}

	function form( $instance ) {
			//Defaults
				$instance = wp_parse_args( (array) $instance, array( 
						'title' => __('Recent Posts', 'random-posts-from-category'),
						'cat' => 1,
						'showposts' => 1,
						'content' => 'title',
						'postlink' => 0,
						'words' => '99999',
						'tags' => '<p><div><span><br><img><a><ul><ol><li><blockquote><cite><em><i><strong><b><h2><h3><h4><h5><h6>'));	
	?>  
       
<p>
<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'random-posts-from-category'); ?></label> 
<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" 
	name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $instance['title']; ?>" />
</p>

<p><label for="<?php echo $this->get_field_id('cat'); ?>"><?php _e('Show posts from category:', 'random-posts-from-category'); ?></label> 
<?php wp_dropdown_categories(array('name' => $this->get_field_name('cat'), 'hide_empty'=>0, 'hierarchical'=>1, 'selected'=>$instance['cat'])); ?></label>
</p>

<p>
<input id="<?php echo $this->get_field_id('postlink'); ?>" name="<?php echo $this->get_field_name('postlink'); ?>" 
	type="checkbox" <?php if ($instance['postlink']) { ?> checked="checked" <?php } ?> value="1" />
<label for="<?php echo $this->get_field_id('postlink'); ?>"><?php _e('Link widget title to category archive', 'random-posts-from-category'); ?></label>
</p>

<p><label for="<?php echo $this->get_field_id('showposts'); ?>"><?php _e('Number of posts to show:', 'random-posts-from-category'); ?></label> 
<input class="widefat" id="<?php echo $this->get_field_id('showposts'); ?>" name="<?php echo $this->get_field_name('showposts'); ?>" 
	type="text" value="<?php echo $instance['showposts']; ?>" />
</p>

<p>
<label for="<?php echo $this->get_field_id('content'); ?>"><?php _e('Display:', 'random-posts-from-category'); ?></label> 
<select id="<?php echo $this->get_field_id('content'); ?>" name="<?php echo $this->get_field_name('content'); ?>" class="postform">
	<option value="title"<?php selected( $instance['content'], 'title' ); ?>><?php _e('Title Only', 'random-posts-from-category'); ?></option>
	<option value="excerpt"<?php selected( $instance['content'], 'excerpt' ); ?>><?php _e('Title and Excerpt', 'random-posts-from-category'); ?></option>
	<option value="content"<?php selected( $instance['content'], 'content' ); ?>><?php _e('Title and Content', 'random-posts-from-category'); ?></option>
</select>
</p>

<?php
if (function_exists('the_excerpt_reloaded')) { ?>
<p>
<label for="<?php echo $this->get_field_id('words'); ?>"><?php _e('Limit excerpts to how many words?:', 'random-posts-from-category'); ?></label>
<input class="widefat" id="<?php echo $this->get_field_id('words'); ?>" name="<?php echo $this->get_field_name('words'); ?>" 
	type="text" value="<?php echo $instance['words']; ?>" />
</p>

<p>
<label for="<?php echo $this->get_field_id('tags'); ?>"><?php _e('Allowed HTML tags in excerpts:', 'random-posts-from-category'); ?></label>
<input class="widefat" id="<?php echo $this->get_field_id('tags'); ?>" name="<?php echo $this->get_field_name('tags'); ?>" 
	type="text" value="<?php echo htmlspecialchars($instance['tags'], ENT_QUOTES); ?>" />
<br />
<small><?php _e('E.g.: ', 'random-posts-from-category'); ?>&lt;p&gt;&lt;div&gt;&lt;span&gt;&lt;br&gt;&lt;img&gt;&lt;a&gt;&lt;ul&gt;&lt;ol&gt;&lt;li&gt;&lt;blockquote&gt;&lt;cite&gt;&lt;em&gt;&lt;i&gt;&lt;strong&gt;&lt;b&gt;&lt;h2&gt;&lt;h3&gt;&lt;h4&gt;&lt;h5&gt;&lt;h6&gt;
</small>
</p>
<?php } // end if function_exists

	} // function form
} // widget class

function random_from_cat_init() {
	register_widget('RandomPostsFromCategory');
}

add_action('widgets_init', 'random_from_cat_init');

// i18n
$plugin_dir = basename(dirname(__FILE__)). '/languages';
load_plugin_textdomain( 'RandomPostsFromCategory', 'wp-content/plugins/' . $plugin_dir, $plugin_dir );
?>
