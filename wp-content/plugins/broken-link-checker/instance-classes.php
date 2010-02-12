<?php

/**
 * @author W-Shadow 
 * @copyright 2009
 */

if (!class_exists('blcLinkInstance')) {
class blcLinkInstance {
	
	//Object state
	var $is_new = false;
	
	//DB fields
	var $instance_id = 0;
	var $link_id = 0;
	var $source_id = 0;
	var $source_type = '';
	var $link_text = '';
	var $instance_type = '';
	
	//These are used to pass info to callbacks when editing an instance
	var $old_url = null;
	var $new_url = null;
	
  /**
   * blcLinkInstance::__construct()
   * Class constructor
   *
   * @param mixed $arg XXXXX look up how to do a multiline doc here (phpdoc)
   * @return void
   */
	function __construct($arg = null){
		
		if (is_int($arg)){
			//Load an instance with ID = $arg from the DB.
			$q = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}blc_instances WHERE instance_id=%d LIMIT 1", $arg);
			$arr = $wpdb->get_row( $q, ARRAY_A );
			
			if ( is_array($arr) ){ //Loaded successfully
				$this->set_values($arr);
			} else { 
				//Link instance not found. The object is invalid. 
			}			
			
		} else if (is_array($arg)){
			$this->set_values($arg);
			
			//Is this a new instance?
			$this->is_new = empty($this->instance_id);
			
		} else {
			$this->is_new = true;
		}
	}
	
  /**
   * blcLinkInstance::blcLinkInstance()
   * Old-style constructor for PHP 4. Do not use.
   *
   * @param mixed $arg
   * @return void
   */
	function blcLinkInstance($arg = null){
		$this->__construct($arg);
	}
	
  /**
   * blcLinkInstance::valid()
   * Verifies whether the object represents a valid link instance
   *
   * @return bool
   */
	function valid(){
		//Some basic validation to ensure the required properties are set.
		return !empty($this->link_id) && !empty($this->instance_type) && !empty($this->source_id) 
			&& !empty($this->source_type) && (!empty($this->instance_id) || $this->is_new); 
	}
	
  /**
   * blcLinkInstance::set_values()
   * Set property values to the ones provided in an array (doesn't sanitize).
   *
   * @param array $arr An associative array
   * @return void 
   */
	function set_values($arr){
		foreach( $arr as $key => $value ){
			$this->$key = $value;
		}
	}
	
  /**
   * blcLinkInstance::edit()
   * Replace this instance's URL with a new one.
   * Warning : this shouldn't be called directly. Use blcLink->edit() instead.  
   *
   * @param string $new_url
   * @return bool
   */
	function edit($old_url, $new_url){
		echo "Error : The stub function blcLinkInstance->edit() was executed!\r\n";
		return false;
	}
	
  /**
   * blcLinkInstance::unlink()
   * Remove this instance from the post/blogroll/etc. Also deletes the appropriate DB record(s).
   *
   * @return bool
   */
	function unlink( $url = null ) {
		//FB::warn("The stub function blcLinkInstance->unlink() was executed!");
		return false;
	}
	
  /**
   * blcLinkInstance::forget()
   * Remove the link instance record from database. Doesn't affect the post/link/whatever.
   *
   * @return mixed 1 on success, 0 if the instance wasn't found, false on error
   */
	function forget(){
		global $wpdb;
		
		if ( !$this->valid() ) return false;
		
		if ( !empty($this->link_id) ) {
			$rez = $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}blc_instances WHERE instance_id=%d", $this->instance_id) );
			$this->link_id = 0;
			return $rez;
		} else {
			return false;
		}
	}
	
  /**
   * blcLinkInstance::save()
   * Store the instance in the database.
   *
   * @return bool TRUE on success, FALSE on failure
   */
	function save(){
		global $wpdb;
		
		if ( !$this->valid() ) return false;
		
		if ( $this->is_new ){
			
			//Insert a new row
			$q = "
			INSERT INTO {$wpdb->prefix}blc_instances
				  ( link_id, source_id, source_type, link_text, instance_type )
			VALUES( %d,      %d,        %s,          %s,        %s )";
			
			$q = $wpdb->prepare($q, $this->link_id, $this->source_id, $this->source_type, 
				$this->link_text, $this->instance_type );
				
			$rez = $wpdb->query($q);
			$rez = $rez !== false;
			
			if ($rez){
				$this->instance_id = $wpdb->insert_id;
				//If the instance was successfully saved then it's no longer "new".
				$this->is_new = !$rez;
			}
				
			return $rez;
									
		} else {
			
			//Create a new DB record
			$q = "UPDATE {$wpdb->prefix}blc_instances 
				  SET link_id = %d, source_id = %d, source_type = %s, link_text = %s, instance_type = %s
				  WHERE instance_id = %d";
				  
			$q = $wpdb->prepare($q, $this->link_id, $this->source_id, $this->source_type, 
				$this->link_text, $this->instance_type, $this->instance_id );
				
			$rez = $wpdb->query($q) !== false;
			
			if ($rez){
				//FB::info($this, "Instance updated");
			} else {
				//FB::error("DB error while updating instance {$this->instance_id} : {$wpdb->last_error}");
			}
			
			return  $rez;
						
		}
	}
	
  /**
   * blcLinkInstance::get_url()
   * Get the URL associated with this instance 
   *
   * @return string
   */
	function get_url(){
		if ( !$this->valid() ){
			return false;
		}
		
		//If the URL isn't specified get it from the link record
		$link = new blcLink( intval($this->link_id) );
		return $link->url;
	}
}

class blcLinkInstance_post_link extends blcLinkInstance {
	
	var $post_permalink = '';
	var $changed_links = 0;
	
	function edit($old_url, $new_url){
		global $wpdb;
		
		if ( !$this->valid() ){
			return false;
		}
		
		//If the old URL isn't specified get it from the link record
		if ( empty($old_url) ){
			$old_url = $this->get_url();
		}
		
		//Load the post
		$post = get_post($this->source_id, ARRAY_A);
		if (!$post){
			//FB::error('Can\'t load post ' . $this->source_id);
			return false;
		}
		//FB::info('Post ' . $this->source_id . ' loaded successfully');
		//Figure out the post's permalink - it'll be needed when normalizing relative URLs
		$this->post_permalink = get_permalink( $post['ID'] );
		
		$this->old_url = $old_url;
		$this->new_url = $new_url;
		
		//Track how many links in the post are successfully edited so that we can report an error if none are. 
		$this->changed_links = 0;

		//Find all links and replace those that match $old_url.
		$content = preg_replace_callback(blcUtility::link_pattern(), array(&$this, 'edit_callback'), $post['post_content']);

		if ( $this->changed_links <= 0 ){
			//FB::error("Didn't find any links to edit in this post!");
			return false;
		}
		
		//Clear the post/page cache. This ensures that any further calls to this method
		//will not load the post content from the cache and thus discard the changes
		//we just made.
		if ( 'page' == $post['post_type'] )
			clean_page_cache($this->source_id);
		else
			clean_post_cache($this->source_id);
			
		//Update the post
		$rez = $wpdb->update(
			$wpdb->posts,
			array( 'post_content' => $content ),
			array( 'id' => $this->source_id )
		);
		
		return $rez !== false;
	}
	
	function edit_callback($matches){
		$url = blcUtility::normalize_url($matches[3], $this->post_permalink);
		//FB::log('Found a link with URL "' . $matches[3] . '", normalized URL = "' . $url . '"');
		
		if ($url == $this->old_url){
			//FB::log('Changing this link');
			$this->changed_links++;
			return $matches[1].$matches[2].$this->new_url.$matches[2].$matches[4].$matches[5].$matches[6];
		} else {
			return $matches[0];
		}
	}
	
	function unlink( $url = null ){
		global $wpdb;
		
		if ( !$this->valid() ){
			return false;
		}
		
		//If the URL isn't specified get it from the link record
		if ( empty($url) ){
			$url = $this->get_url();
		}
		
		//Load the post
		$post = get_post($this->source_id, ARRAY_A);
		if (!$post){
			//FB::error('Can\'t load post ' . $this->source_id);
			return false;
		}
		//FB::info('Post ' . $this->source_id . ' loaded successfully');
		//Figure out the post's permalink - it'll be needed when normalizing relative URLs
		$this->post_permalink = get_permalink( $post['ID'] );
		
		//Track how many links in the post are successfully removed so that we can report an error if none are. 
		$this->changed_links = 0;
		
		//Find all links and remove those that match $url.
		$this->old_url = $url; //used by the callback
		$content = preg_replace_callback(blcUtility::link_pattern(), array(&$this, 'unlink_callback'), $post['post_content']);
		
		if ( $this->changed_links <= 0 ){
			return false;
		}
		
		//Clear the post/page cache. This ensures that any further calls to this method
		//will not load the post content from the cache and thus discard the changes
		//we just made.
		if ( 'page' == $post['post_type'] )
			clean_page_cache($this->source_id);
		else
			clean_post_cache($this->source_id);
			
		//Update the post
		$rez = $wpdb->update(
			$wpdb->posts,
			array( 'post_content' => $content ),
			array( 'id' => $this->source_id )
		);
		
		if ( $rez !== false ){
			//Delete the instance record
			//FB::info("Post updated, deleting instance from DB");
			return $this->forget() !== false;
		} else {
			//FB::error("Failed to update the post");
			return false;
		};
	}
	
  /**
   * blcLinkInstance_post_link::unlink_callback()
   * Remove the link while leaving the anchor text intact.
   *
   * @uses $blc_config_manager Global variable pointing to the plugin's configuration manager
   *
   * @param array $matches
   * @return string
   */
	function unlink_callback($matches){
		global $blc_config_manager;
		
		$url = blcUtility::normalize_url($matches[3], $this->post_permalink);
		
		//Does the URL match?
		if ($url == $this->old_url){
			$this->changed_links++;
			if ( $blc_config_manager->options['mark_removed_links'] ){
				//leave only the anchor text + the removed_link CSS class
				return '<span class="removed_link">' . $matches[5] . '</span>'; 
			} else {
				return $matches[5]; //just the anchor text
			}
			
		} else {
			return $matches[0]; //return the link unchanged
		}
	}
		
}

class blcLinkInstance_post_image extends blcLinkInstance {
	
	var $post_permalink = '';
	var $changed_images = 0;
	
	function edit($old_url, $new_url){
		global $wpdb;
		
		if ( !$this->valid() ){
			return false;
		}
	
		//If the URL isn't specified get it from the link record
		if ( empty($old_url) ){
			$old_url = $this->get_url();
		}
		
		//Load the post
		$post = get_post($this->source_id, ARRAY_A);
		if (!$post){
			return false;
		}
		//Figure out the post's permalink - it'll be needed when normalizing relative URLs
		$this->post_permalink = get_permalink( $post['ID'] );
		
		$this->old_url = $old_url;
		$this->new_url = $new_url;
		
		//Find all images and change the URL of those that match $old_url.
		//Note : this might be inefficient if there's more than one instance of the same link
		//in one post, as each instances would be called when editing the link.
		//Either way, I thing the overhead is small enough to ignore for now.
		$this->changed_images = 0; 
		$content = preg_replace_callback(blcUtility::img_pattern(), array(&$this, 'edit_callback'), $post['post_content']);
		
		if ( $this->changed_images <= 0 ){
			return false;
		}
		
		//Clear the post/page cache. This ensures that any further calls to this method
		//will not load the post content from the cache and thus discard the changes
		//we just made.
		if ( 'page' == $post['post_type'] )
			clean_page_cache($this->source_id);
		else
			clean_post_cache($this->source_id);
			
		//Save the modified post
		$rez = $wpdb->update(
			$wpdb->posts,
			array( 'post_content' => $content ),
			array( 'id' => $this->source_id )
		);
		
		return $rez !== false;
	}
	
	function edit_callback($matches){
		$url = blcUtility::normalize_url($matches[3], $this->post_permalink);
		
		if ($url == $this->old_url){
			$this->changed_images++;
			return $matches[1].$matches[2].$this->new_url.$matches[2].$matches[4].$matches[5];
		} else {
			return $matches[0];
		}
	}
	
	function unlink( $url = null ){
		global $wpdb;
		
		if ( !$this->valid() ){
			return false;
		}
		
		//If the URL isn't specified get it from the link record
		if ( empty($url) ){
			$url = $this->get_url();
		}
		
		//Load the post
		$post = get_post($this->source_id, ARRAY_A);
		if (!$post){
			//FB::error('Can\'t load post ' . $this->source_id);
			return false;
		}
		//FB::info('Post ' . $this->source_id . ' loaded successfully');
		//Figure out the post's permalink - it'll be needed when normalizing relative URLs
		$this->post_permalink = get_permalink( $post['ID'] );
		
		//Find all links and remove those that match $url.
		$this->old_url = $url; //used by the callback
		$this->changed_images = 0;
		$content = preg_replace_callback(blcUtility::img_pattern(), array(&$this, 'unlink_callback'), $post['post_content']);
		
		if ( $this->changed_images <= 0 ){
			return false;
		}
		
		//Clear the post/page cache. This ensures that any further calls to this method
		//will not load the post content from the cache and thus discard the changes
		//we just made.
		if ( 'page' == $post['post_type'] )
			clean_page_cache($this->source_id);
		else
			clean_post_cache($this->source_id);
			
		//Save the modified post
		$rez = $wpdb->update(
			$wpdb->posts,
			array( 'post_content' => $content ),
			array( 'id' => $this->source_id )
		);
		
		if ( $rez !== false ){
			//Delete the instance record
			//FB::info("Post updated, deleting instance from DB");
			return $this->forget() !== false;
		} else {
			//FB::error("Failed to update the post");
			return false;
		};
	}
	
	function unlink_callback($matches){
		$url = blcUtility::normalize_url($matches[3], $this->post_permalink);
		
		if ($url == $this->old_url){
			$this->changed_images++;
			return ''; //remove the image completely
		} else {
			return $matches[0]; //return the image unchanged
		}
	}
	
}

class blcLinkInstance_custom_field_link extends blcLinkInstance {
	
	function edit($old_url, $new_url){
		if ( !$this->valid() ){
			return false;
		}
	
		//If the URL isn't specified get it from the link record
		if ( empty($old_url) ){
			$old_url = $this->old_url;
		}
		
		//FB::log("Changing [{$this->link_text}] to '$new_url' on post {$this->source_id}");
		//Change the meta value
		return update_post_meta( $this->source_id, $this->link_text, $new_url, $old_url );
	}
	
	function unlink( $url = null ){
		//Get the URL from the link record if it wasn't specified
		if ( empty($url) ){
			$url = $this->get_url();
		}
		
		//FB::log("Removing [{$this->link_text}] from post {$this->source_id} where value is '$url'");
		delete_post_meta( $this->source_id, $this->link_text, $url );
		//TODO: Make unlink work for custom fields where the URL is only the first line, not the entire value
		
		return $this->forget() !== false;
	}
	
}

class blcLinkInstance_blogroll_link extends blcLinkInstance {
	
	function edit($old_url, $new_url){
		if ( !$this->valid() ){
			return false;
		}
	
		//FB::log("Changing the bookmark [{$this->link_text}] to '$new_url'");
		//Update the bookmark. Note : wp_update_link calls the edit_link hook, which is also 
		//hooked by the plugin for maintaining bookmark->instance integrity... Conclusion : 
		//don't ever call $instance->edit() in that hook!
		return wp_update_link( array(
			'link_id' => $this->source_id,
			'link_url' => $new_url 
		 ) );
	}
	
	function unlink( $url = null ){
		if ( !$this->valid() ){
			return false;
		}
		
		//Get the URL from the link record if it wasn't specified
		if ( empty($url) ){
			$url = $this->get_url();
		}
		
		//FB::log("Removing bookmark [{$this->link_text}] ( ID : {$this->source_id} )");
		//Note : wp_delete_link calls the delete_link hook, which is also used by the plugin
		//for removing instances associated with links deleted through the WP link manager.
		//This means that when you delete a bookmark via the plugin's interface, the plugin will 
		//attempt to delete it twice. Anybody have a better idea?
		if ( wp_delete_link( $this->source_id ) ){
			return $this->forget() !== false;
		} else {
			//FB::error("Failed to delete the bookmark.");
			return false;
		};
		
	}
	
}

}//class_exists

?>