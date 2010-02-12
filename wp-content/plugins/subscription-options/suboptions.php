<?php
/**
 * Plugin Name: Subscription Options
 * Plugin URI: http://digitalcortex.net/plugins
 * Description: Adds subscription option icons for your RSS Feed; your FeedBurner Email Service; your Twitter Stream and even your Facebook page. 12 colour options. Totally user-defined.
 * Version: 0.6.4
 * Author: Tom Saunter
 * Author URI: http://digitalcortex.net
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

/**
 * Add function to widgets_init that will load the widget.
 */
add_action( 'widgets_init', 'suboptions_load_widgets' );

/**
 * Register the widget.
 * 'suboptions_widget' is the widget class used below.
 */
function suboptions_load_widgets() {
	register_widget( 'suboptions_widget' );
}

/**
 * suboptions Widget class.
 * This class handles everything that needs to be handled with the widget:
 * the settings, form, display, and update.  Nice!
 */
class suboptions_widget extends WP_Widget {

	/**
	 * Widget setup.
	 */
	function suboptions_widget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'suboptions', 'description' => __('Add subscription options for your readers with related feed icons', 'suboptions') );

		/* Widget control settings. */
		$control_ops = array( 'width' => 260, 'height' => 350, 'id_base' => 'suboptions-widget' );

		/* Create the widget. */
		$this->WP_Widget( 'suboptions-widget', __('Subscription Options', 'suboptions'), $widget_ops, $control_ops );
	}

	/**
	 * Display the widget on the screen.
	 */
	function widget( $args, $instance ) {
		extract( $args );

		/* Variables from the widget settings. */
		$title = apply_filters('widget_title', $instance['title'] );
		$rss_url = $instance['rss_url'];
		$mail_url = $instance['mail_url'];
		$twitter_url = $instance['twitter_url'];
		$facebook_url = $instance['facebook_url'];
		$size = $instance['size'];
		$rss_col = $instance['rss_col'];
		$mail_col = $instance['mail_col'];
		$twitter_col = $instance['twitter_col'];
		$facebook_col = $instance['facebook_col'];

		/* Before widget (defined by themes). */
		echo $before_widget;

		/* Display the widget title if one was input (before and after defined by themes). */
		if ( $title )
			echo $before_title . $title . $after_title;

		/* If an RSS Feed URL was entered, display the RSS icon. */			
		if ( $rss_url )
			echo '<a target="_blank" title="Subscribe via RSS" href="'.$rss_url.'"><img class="rss_icon" alt="Subscribe via RSS" style="border: 0px none; width: 34px; height: 35px; " src="'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/subscription-options/images/rss.png"/> </a>';
				
		/* If a FeedBurner Email Service URL was entered, display the email icon. */			
		if ( $mail_url )
			echo '<a target="_blank" title="Subscribe via Email" href="'.$mail_url.'"><img class="mail_icon" alt="Subscribe via Email" style="border: 0px none; width: '.$size.'px; height: '.$size.'px; " src="'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/subscription-options/images/mail_icon_'.$mail_col.'.png"/> </a>';
			
		/* If a Twitter Stream URL was entered, display the Twitter icon. */			
		if ( $twitter_url )
			echo '<a target="_blank" title="Subscribe via Twitter" href="'.$twitter_url.'"><img class="twitter_icon" alt="Subscribe via Twitter" style="border: 0px none; width: 34px; height: 35px; " src="'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/subscription-options/images/twitter.png"/> </a>';

		/* If a Twitter Stream URL was entered, display the Twitter icon. */			
		if ( $facebook_url )
			echo '<a target="_blank" title="Subscribe via Facebook" href="'.$facebook_url.'"><img class="twitter_icon" alt="Subscribe via Facebook" style="border: 0px none; width: 34px; height: 35px; " src="'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/subscription-options/images/facebook.png"/> </a>';

		/* After widget (defined by themes). */
		echo '<div class="clr"></div>' . $after_widget;
	}

	/**
	 * Update the widget settings.
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip HTML tags for the following: */
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['rss_url'] = strip_tags( $new_instance['rss_url'] );
		$instance['mail_url'] = strip_tags( $new_instance['mail_url'] );
		$instance['twitter_url'] = strip_tags( $new_instance['twitter_url'] );
		$instance['facebook_url'] = strip_tags( $new_instance['facebook_url'] );
		$instance['size'] = strip_tags( $new_instance['size'] );
		$instance['rss_col'] = strip_tags( $new_instance['rss_col'] );
		$instance['mail_col'] = strip_tags( $new_instance['mail_col'] );
		$instance['twitter_col'] = strip_tags( $new_instance['twitter_col'] );
		$instance['facebook_col'] = strip_tags( $new_instance['facebook_col'] );

		return $instance;
	}

	/**
	 * Displays the widget settings controls on the widget panel.
	 * Makes use of the get_field_id() and get_field_name() function
	 * when creating your form elements. This handles the confusing stuff.
	 */
	function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array(
			'title' => 'Subscription Options:',
			'rss_url' => '',
			'mail_url' => '',
			'twitter_url' => '',
			'facebook_url' => '',
			'size' => '70',
			'rss_col' => '9',
			'mail_col' => '1',
			'twitter_col' => '4',
			'facebook_col' => '7',
          );
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:</label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:218px;" />
		</p>

		<!-- RSS Feed URL & Colour ID: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'rss_url' ); ?>"><?php _e('RSS Feed URL & Colour:', 'suboptions'); ?></label>
			<input id="<?php echo $this->get_field_id( 'rss_url' ); ?>" name="<?php echo $this->get_field_name( 'rss_url' ); ?>" value="<?php echo $instance['rss_url']; ?>" style="width:218px;" /><input id="<?php echo $this->get_field_id( 'rss_col' ); ?>" name="<?php echo $this->get_field_name( 'rss_col' ); ?>" value="<?php echo $instance['rss_col']; ?>" style="width:23px;" />
		</p>

		<!-- FeedBurner Email Service URL & Colour ID: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'mail_url' ); ?>"><?php _e('FeedBurner Email Service URL & Colour:', 'suboptions'); ?></label>
			<input id="<?php echo $this->get_field_id( 'mail_url' ); ?>" name="<?php echo $this->get_field_name( 'mail_url' ); ?>" value="<?php echo $instance['mail_url']; ?>" style="width:218px;" /><input id="<?php echo $this->get_field_id( 'mail_col' ); ?>" name="<?php echo $this->get_field_name( 'mail_col' ); ?>" value="<?php echo $instance['mail_col']; ?>" style="width:23px;" />
			</p>
		
		<!-- Twitter Stream URL & Colour ID: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'twitter_url' ); ?>"><?php _e('Twitter Stream URL & Colour:', 'suboptions'); ?></label>
			<input id="<?php echo $this->get_field_id( 'twitter_url' ); ?>" name="<?php echo $this->get_field_name( 'twitter_url' ); ?>" value="<?php echo $instance['twitter_url']; ?>" style="width:218px;" /><input id="<?php echo $this->get_field_id( 'twitter_col' ); ?>" name="<?php echo $this->get_field_name( 'twitter_col' ); ?>" value="<?php echo $instance['twitter_col']; ?>" style="width:23px;" />
			</p>
		
		<!-- Facebook Page URL & Colour ID: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'facebook_url' ); ?>"><?php _e('Facebook Page URL & Colour:', 'suboptions'); ?></label>
			<input id="<?php echo $this->get_field_id( 'facebook_url' ); ?>" name="<?php echo $this->get_field_name( 'facebook_url' ); ?>" value="<?php echo $instance['facebook_url']; ?>" style="width:218px;" /><input id="<?php echo $this->get_field_id( 'facebook_col' ); ?>" name="<?php echo $this->get_field_name( 'facebook_col' ); ?>" value="<?php echo $instance['facebook_col']; ?>" style="width:23px;" />	
			</p>
		
		<!-- Colour Reference: Image -->
		<p>
			<label><?php _e('IDs:', 'suboptions'); ?></label>
			<img style="margin: 0pt 0pt -6px 5px;" alt="if you can't see colours go to http://digitalcortex.net/plugins" src="../wp-content/plugins/subscription-options/images/colour-options.png"/>
		</p>

		<!-- Icon Size: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'size' ); ?>"><?php _e('Icon Size:', 'suboptions'); ?></label><input id="<?php echo $this->get_field_id( 'size' ); ?>" name="<?php echo $this->get_field_name( 'size' ); ?>" value="<?php echo $instance['size']; ?>" style="width:30px; " /><?php _e(' pixels', 'suboptions'); ?>	
		</p>

	<?php
	}
}

?>