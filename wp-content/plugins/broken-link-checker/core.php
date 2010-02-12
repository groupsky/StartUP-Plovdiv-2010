<?php

//The plugin will use Snoopy in case CURL is not available
if (!class_exists('Snoopy')) require_once(ABSPATH. WPINC . '/class-snoopy.php');

/**
 * Simple function to replicate PHP 5 behaviour
 */
if ( !function_exists( 'microtime_float' ) ) {
	function microtime_float()
	{
	    list($usec, $sec) = explode(" ", microtime());
	    return ((float)$usec + (float)$sec);
	}
}

if (!class_exists('wsBrokenLinkChecker')) {

class wsBrokenLinkChecker {
    var $conf;
    
	var $loader;
    var $my_basename = '';	
    
    var $db_version = 3;
    
    var $execution_start_time; 	//Used for a simple internal execution timer in start_timer()/execution_time()
    var $lockfile_handle = null; 
    
    var $native_filters = null;

  /**
   * wsBrokenLinkChecker::wsBrokenLinkChecker()
   * Class constructor
   *
   * @param string $loader The fully qualified filename of the loader script that WP identifies as the "main" plugin file.
   * @param blcConfigurationManager $conf An instance of the configuration manager
   * @return void
   */
    function wsBrokenLinkChecker ( $loader, $conf ) {
        global $wpdb;
        
        $this->loader = $loader;
        $this->conf = $conf;

        add_action('activate_' . plugin_basename( $this->loader ), array(&$this,'activation'));
        $this->my_basename = plugin_basename( $this->loader );
        
        add_action('init', array(&$this,'load_language'));
        
        add_action('admin_menu', array(&$this,'admin_menu'));

        //These hooks update the plugin's internal records when posts are added, deleted or modified.
		add_action('delete_post', array(&$this,'post_deleted'));
        add_action('save_post', array(&$this,'post_saved'));
        //Treat post trashing/untrashing as delete/save. 
        add_action('trash_post', array(&$this,'post_deleted'));
        add_action('untrash_post', array(&$this,'post_saved'));
        
        //These do the same for (blogroll) links.
        add_action('add_link', array(&$this,'hook_add_link'));
        add_action('edit_link', array(&$this,'hook_edit_link'));
        add_action('delete_link', array(&$this,'hook_delete_link'));
        
        //TODO: Hook the delete_post_meta action to detect when custom fields are deleted directly
        
		//Load jQuery on Dashboard pages (probably redundant as WP already does that)
        add_action('admin_print_scripts', array(&$this,'admin_print_scripts'));
        
        //The dashboard widget
        add_action('wp_dashboard_setup', array(&$this, 'hook_wp_dashboard_setup'));
		
        //AJAXy hooks
        //TODO: Check nonces in AJAX hooks
        add_action( 'wp_ajax_blc_full_status', array(&$this,'ajax_full_status') );
        add_action( 'wp_ajax_blc_dashboard_status', array(&$this,'ajax_dashboard_status') );
        add_action( 'wp_ajax_blc_work', array(&$this,'ajax_work') );
        add_action( 'wp_ajax_blc_discard', array(&$this,'ajax_discard') );
        add_action( 'wp_ajax_blc_edit', array(&$this,'ajax_edit') );
        add_action( 'wp_ajax_blc_link_details', array(&$this,'ajax_link_details') );
        add_action( 'wp_ajax_blc_exclude_link', array(&$this,'ajax_exclude_link') );
        add_action( 'wp_ajax_blc_unlink', array(&$this,'ajax_unlink') );
        
        //Check if it's possible to create a lockfile and nag the user about it if not.
        if ( $this->lockfile_name() ){
            //Lockfiles work, so it's safe to enable the footer hook that will call the worker
            //function via AJAX.
            add_action('admin_footer', array(&$this,'admin_footer'));
        } else {
            //No lockfiles, nag nag nag!
            add_action( 'admin_notices', array( &$this, 'lockfile_warning' ) );
        }
        
        //Initialize the built-in link filters
        add_action('init', array(&$this,'init_native_filters'));
    }

    function admin_footer(){
        ?>
        <!-- wsblc admin footer -->
        <div id='wsblc_updater_div'></div>
        <script type='text/javascript'>
        (function($){
				
			//(Re)starts the background worker thread 
			function blcDoWork(){
				$.post(
					"<?php echo admin_url('admin-ajax.php'); ?>",
					{
						'action' : 'blc_work'
					}
				);
			}
			//Call it the first time
			blcDoWork();
			
			//Then call it periodically every X seconds 
			setInterval(blcDoWork, <?php echo (intval($this->conf->options['max_execution_time']) + 1 )*1000; ?>);
			
		})(jQuery);
        </script>
        <!-- /wsblc admin footer -->
        <?php
    }

    function is_excluded($url){
        if (!is_array($this->conf->options['exclusion_list'])) return false;
        foreach($this->conf->options['exclusion_list'] as $excluded_word){
            if (stristr($url, $excluded_word)){
                return true;
            }
        }
        return false;
    }

    function dashboard_widget(){
        ?>
        <p id='wsblc_activity_box'><?php _e('Loading...', 'broken-link-checker');  ?></p>
        <script type='text/javascript'>
        	jQuery( function($){
        		var blc_was_autoexpanded = false;
        		
				function blcDashboardStatus(){
					$.getJSON(
						"<?php echo admin_url('admin-ajax.php'); ?>",
						{
							'action' : 'blc_dashboard_status'
						},
						function (data, textStatus){
							if ( data && ( typeof(data.text) != 'undefined' ) ) {
								$('#wsblc_activity_box').html(data.text); 
								<?php if ( $this->conf->options['autoexpand_widget'] ) { ?>
								//Expand the widget if there are broken links.
								//Do this only once per pageload so as not to annoy the user.
								if ( !blc_was_autoexpanded && ( data.status.broken_links > 0 ) ){
									$('#blc_dashboard_widget.postbox').removeClass('closed');
									blc_was_autoexpanded = true;
								};
								<?php } ?>
							} else {
								$('#wsblc_activity_box').html('<?php _e('[ Network error ]', 'broken-link-checker'); ?>');
							}
							
							setTimeout( blcDashboardStatus, 120*1000 ); //...update every two minutes
						}
					);
				}
				
				blcDashboardStatus();//Call it the first time
			
			} );
        </script>
        <?php
    }
    
    function dashboard_widget_control( $widget_id, $form_inputs = array() ){
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] && 'blc_dashboard_widget' == $_POST['widget_id'] ) {
			//It appears $form_inputs isn't used in the current WP version, so lets just use $_POST
			$this->conf->options['autoexpand_widget'] = !empty($_POST['blc-autoexpand']);
			$this->conf->save_options();
		}
	
		?>
		<p><label for="blc-autoexpand">
			<input id="blc-autoexpand" name="blc-autoexpand" type="checkbox" value="1" <?php if ( $this->conf->options['autoexpand_widget'] ) echo 'checked="checked"'; ?> />
			<?php _e('Automatically expand the widget if broken links have been detected', 'broken-link-checker'); ?>
		</label></p>
		<?php
    }

    function admin_print_scripts(){
        //jQuery is used for AJAX and effects
        wp_enqueue_script('jquery');
    }
    
    function load_ui_scripts(){
    	//jQuery UI is used on the settings page and in the link listings
		wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-dialog');
	}

  /**
   * ws_broken_link_checker::post_deleted()
   * A hook for post_deleted. Remove link instances associated with that post. 
   *
   * @param int $post_id
   * @return void
   */
    function post_deleted($post_id){
        global $wpdb;
        
        //FB::log($post_id, "Post deleted");        
        //Remove this post's instances
        $q = "DELETE FROM {$wpdb->prefix}blc_instances 
			  WHERE source_id = %d AND (source_type = 'post' OR source_type='custom_field')";
		$q = $wpdb->prepare($q, intval($post_id) );
		
		//FB::log($q, 'Executing query');
		
        if ( $wpdb->query( $q ) === false ){
			//FB::error($wpdb->last_error, "Database error");
		}
        
        //Remove the synch record
        $q = "DELETE FROM {$wpdb->prefix}blc_synch 
			  WHERE source_id = %d AND source_type = 'post'";
        $wpdb->query( $wpdb->prepare($q, intval($post_id)) );
        
        //Remove any dangling link records
        $this->cleanup_links();
    }

    function post_saved($post_id){
        global $wpdb;

        $post = get_post($post_id);
        //Only check links in posts, not revisions and attachments
        if ( ($post->post_type != 'post') && ($post->post_type != 'page') ) return null;
        //Only check published posts
        if ( $post->post_status != 'publish' ) return null;
        
        $this->mark_unsynched( $post_id, 'post' );
    }
    
    function initiate_recheck(){
    	global $wpdb;
    	
    	//Delete all discovered instances
    	$wpdb->query("TRUNCATE {$wpdb->prefix}blc_instances");
    	
    	//Delete all discovered links
    	$wpdb->query("TRUNCATE {$wpdb->prefix}blc_links");
    	
    	//Mark all posts, custom fields and bookmarks for processing.
    	$this->resynch();
	}

    function resynch(){
    	global $wpdb;
    	
    	//Drop all synchronization records
    	$wpdb->query("TRUNCATE {$wpdb->prefix}blc_synch");
    	
    	//Create new synchronization records for posts 
    	$q = "INSERT INTO {$wpdb->prefix}blc_synch(source_id, source_type, synched)
			  SELECT id, 'post', 0
			  FROM {$wpdb->posts}
			  WHERE
			  	{$wpdb->posts}.post_status = 'publish'
 				AND {$wpdb->posts}.post_type IN ('post', 'page')";
 		$wpdb->query( $q );
 		
 		//Create new synchronization records for bookmarks (the blogroll)
 		$q = "INSERT INTO {$wpdb->prefix}blc_synch(source_id, source_type, synched)
			  SELECT link_id, 'blogroll', 0
			  FROM {$wpdb->links}
			  WHERE 1";
 		$wpdb->query( $q );
    	
		//Delete invalid instances
		$this->cleanup_instances();
		//Delete orphaned links
		$this->cleanup_links();
		
		$this->conf->options['need_resynch'] = true;
		$this->conf->save_options();
	}
	
	function mark_unsynched( $source_id, $source_type ){
		global $wpdb;
		
		$q = "REPLACE INTO {$wpdb->prefix}blc_synch( source_id, source_type, synched, last_synch)
			  VALUES( %d, %s, %d, NOW() )";
		$rez = $wpdb->query( $wpdb->prepare( $q, $source_id, $source_type, 0 ) );
		
		if ( !$this->conf->options['need_resynch'] ){
			$this->conf->options['need_resynch'] = true;
			$this->conf->save_options();
		}
		
		return $rez;
	}
	
	function mark_synched( $source_id, $source_type ){
		global $wpdb;
		//FB::log("Marking $source_type $source_id as synched.");
		$q = "REPLACE INTO {$wpdb->prefix}blc_synch( source_id, source_type, synched, last_synch)
			  VALUES( %d, %s, %d, NOW() )";
		return $wpdb->query( $wpdb->prepare( $q, $source_id, $source_type, 1 ) );
	}
	
    function activation(){
    	//Prepare the database.
        $this->upgrade_database();

		//Clear the instance table and mark all posts and other parse-able objects as unsynchronized. 
        $this->resynch();

        //Save the default options. 
        $this->conf->save_options();
        
        //And optimize my DB tables, too (for good measure) 
        $this->optimize_database();
    }
    
  /**
   * ws_broken_link_checker::upgrade_database()
   * Create and/or upgrade database tables
   *
   * @param bool $die_on_error Whether the function should stop the script and display an error message if a DB error is encountered.  
   * @return void
   */
    function upgrade_database( $die_on_error = true ){
		global $wpdb;
		
		//Do we need to upgrade?
		//[ Disabled for now, was causing issues when the user manually deletes the plugin ]
		//if ( $this->db_version == $this->conf->options['current_db_version'] ) return;
		
		//Delete tables used by older versions of the plugin
		$rez = $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}blc_linkdata, {$wpdb->prefix}blc_postdata" );
		if ( $rez === false ){
			//FB::error($wpdb->last_error, "Database error");
			return false;
		}
		
		require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
		
		//Create the link table if it doesn't exist yet. 
		$q = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}blc_links (
				link_id int(20) unsigned NOT NULL auto_increment,
				url text CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
				last_check datetime NOT NULL default '0000-00-00 00:00:00',
				check_count int(2) unsigned NOT NULL default '0',
				final_url text CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
				redirect_count smallint(5) unsigned NOT NULL,
				log text NOT NULL,
				http_code smallint(6) NOT NULL,
				request_duration float NOT NULL default '0',
				timeout tinyint(1) unsigned NOT NULL default '0',
				  
				PRIMARY KEY  (link_id),
				KEY url (url(150)),
				KEY final_url (final_url(150)),
				KEY http_code (http_code),
				KEY timeout (timeout)
			)";
		if ( $wpdb->query( $q ) === false ){
			if ( $die_on_error )
				die( sprintf( __('Database error : %s', 'broken-link-checker'), $wpdb->last_error) );
		};
		
		//Fix URL fields so that they are collated as case-sensitive (this can't be done via dbDelta)
		$q = "ALTER TABLE {$wpdb->prefix}blc_links 
			  MODIFY	url text CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
			  MODIFY final_url text CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL";
		if ( $wpdb->query( $q ) === false ){
			if ( $die_on_error )
				die( sprintf( __('Database error : %s', 'broken-link-checker'), $wpdb->last_error) );
		};
		
		//Create the instance table
		$q = "CREATE TABLE {$wpdb->prefix}blc_instances (
				instance_id int(10) unsigned NOT NULL auto_increment,
				link_id int(10) unsigned NOT NULL,
				source_id int(10) unsigned NOT NULL,
				source_type enum('post','blogroll','custom_field') NOT NULL default 'post',
				link_text varchar(250) NOT NULL,
				instance_type enum('link','image') NOT NULL default 'link',
				
				PRIMARY KEY  (instance_id),
				KEY link_id (link_id),
				KEY source_id (source_id,source_type),
				FULLTEXT KEY link_text (link_text)
			) ENGINE = MYISAM"; 
		dbDelta($q);
		
		//Create the synchronization table
		$q = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}blc_synch (
			  source_id int(20) unsigned NOT NULL,
			  source_type enum('post','blogroll') NOT NULL,
			  synched tinyint(3) unsigned NOT NULL,
			  last_synch datetime NOT NULL,
			  PRIMARY KEY  (source_id, source_type),
			  KEY synched (synched)
			)";
		if ( $wpdb->query( $q ) === false ){
			if ( $die_on_error )
				die( sprintf( __('Database error : %s', 'broken-link-checker'), $wpdb->last_error) );
		};
		
		//Create the custom filter table
		$q = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}blc_filters (
			  id int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `name` varchar(100) NOT NULL,
			  params text NOT NULL,
			  PRIMARY KEY (id)
			)";
		if ( $wpdb->query( $q ) === false ){
			if ( $die_on_error )
				die( sprintf( __('Database error : %s', 'broken-link-checker'), $wpdb->last_error) );
		};
		
		$this->conf->options['current_db_version'] = $this->db_version;
		$this->conf->save_options();
		
		return true;
	}
	
  /**
   * wsBrokenLinkChecker::optimize_database()
   * Optimize the plugin's tables
   *
   * @return void
   */
	function optimize_database(){
		global $wpdb;
		
		$wpdb->query("OPTIMIZE TABLE {$wpdb->prefix}blc_links, {$wpdb->prefix}blc_instances, {$wpdb->prefix}blc_synch");
	}

    function admin_menu(){
    	if (current_user_can('manage_options'))
          add_filter('plugin_action_links', array(&$this, 'plugin_action_links'), 10, 2);
    	
        $options_page_hook = add_options_page( 
			__('Link Checker Settings', 'broken-link-checker'), 
			__('Link Checker', 'broken-link-checker'), 
			'manage_options',
            'link-checker-settings',array(&$this, 'options_page')
		);
		
        $links_page_hook = add_management_page(
			__('View Broken Links', 'broken-link-checker'), 
			__('Broken Links', 'broken-link-checker'), 
			'edit_others_posts',
            'view-broken-links',array(&$this, 'links_page')
		);
		 
		//Add plugin-specific scripts and CSS only to the it's own pages
		//TODO: Use the admin_enqueue_scripts action to enqueue the scripts
		add_action( 'admin_print_styles-' . $options_page_hook, array(&$this, 'options_page_css') );
        add_action( 'admin_print_styles-' . $links_page_hook, array(&$this, 'links_page_css') );
        add_action( 'admin_print_scripts-' . $options_page_hook, array(&$this, 'load_ui_scripts') );
        add_action( 'admin_print_scripts-' . $links_page_hook, array(&$this, 'load_ui_scripts') );
    }

    /**
   * plugin_action_links()
   * Handler for the 'plugin_action_links' hook. Adds a "Settings" link to this plugin's entry
   * on the plugin list.
   *
   * @param array $links
   * @param string $file
   * @return array
   */
    function plugin_action_links($links, $file) {
        if ($file == $this->my_basename)
            $links[] = "<a href='options-general.php?page=link-checker-settings'>" . __('Settings') . "</a>";
        return $links;
    }

    function mytruncate($str, $max_length=50){
        if(strlen($str)<=$max_length) return $str;
        return (substr($str, 0, $max_length-3).'...');
    }

    function options_page(){
        if (isset($_GET['recheck']) && ($_GET['recheck'] == 'true')) {
            $this->initiate_recheck();
        }
        if(isset($_POST['submit'])) {
			check_admin_referer('link-checker-options');
			
			//The execution time limit must be above zero
            $new_execution_time = intval($_POST['max_execution_time']);
            if( $new_execution_time > 0 ){
                $this->conf->options['max_execution_time'] = $new_execution_time;
            }

			//The check threshold also must be > 0
            $new_check_threshold=intval($_POST['check_threshold']);
            if( $new_check_threshold > 0 ){
                $this->conf->options['check_threshold'] = $new_check_threshold;
            }
            
            $this->conf->options['mark_broken_links'] = !empty($_POST['mark_broken_links']);
            $new_broken_link_css = trim($_POST['broken_link_css']);
            $this->conf->options['broken_link_css'] = $new_broken_link_css;
            
            $this->conf->options['mark_removed_links'] = !empty($_POST['mark_removed_links']);
            $new_removed_link_css = trim($_POST['removed_link_css']);
            $this->conf->options['removed_link_css'] = $new_removed_link_css;

			//TODO: Maybe update affected links when exclusion list changes (could be expensive resource-wise).
            $this->conf->options['exclusion_list'] = array_filter( 
				preg_split( 
					'/[\s\r\n]+/',				//split on newlines and whitespace 
					$_POST['exclusion_list'], 
					-1,
					PREG_SPLIT_NO_EMPTY			//skip empty values
				) 
			);
                
            //Parse the custom field list
            $new_custom_fields = array_filter( 
				preg_split( '/[\s\r\n]+/', $_POST['blc_custom_fields'], -1, PREG_SPLIT_NO_EMPTY )
			);
            
			//Calculate the difference between the old custom field list and the new one (used later)
            $diff1 = array_diff( $new_custom_fields, $this->conf->options['custom_fields'] );
            $diff2 = array_diff( $this->conf->options['custom_fields'], $new_custom_fields );
            $this->conf->options['custom_fields'] = $new_custom_fields;
            
            //Temporary file directory
            $this->conf->options['custom_tmp_dir'] = trim( stripslashes(strval($_POST['custom_tmp_dir'])) );
            
            //HTTP timeout
            $new_timeout = intval($_POST['timeout']);
            if( $new_timeout > 0 ){
                $this->conf->options['timeout'] = $new_timeout ;
            }

            $this->conf->save_options();
			
			/*
			 If the list of custom fields was modified then we MUST resynchronize or
			 custom fields linked with existing posts may not be detected. This is somewhat
			 inefficient.  
			 */
			if ( ( count($diff1) > 0 ) || ( count($diff2) > 0 ) ){
				$this->resynch();
			}
			
			$base_url = remove_query_arg( array('_wpnonce', 'noheader', 'updated', 'error', 'action', 'message') );
			wp_redirect( add_query_arg( array( 'updated' => 1), $base_url ) );
        }
        
		$debug = $this->get_debug_info();
		
		?>

        <div class="wrap"><h2><?php _e('Broken Link Checker Options', 'broken-link-checker'); ?></h2>
		
        <form name="link_checker_options" method="post" action="<?php 
			echo admin_url('options-general.php?page=link-checker-settings&noheader=1'); 
		?>">
        <?php 
			wp_nonce_field('link-checker-options');
		?>

        <table class="form-table">

        <tr valign="top">
        <th scope="row">
			<?php _e('Status','broken-link-checker'); ?>
			<br>
			<a href="javascript:void(0)" id="blc-debug-info-toggle"><?php _e('Show debug info', 'broken-link-checker'); ?></a>
		</th>
        <td>


        <div id='wsblc_full_status'>
            <br/><br/><br/>
        </div>
        <script type='text/javascript'>
        	(function($){
				
				function blcUpdateStatus(){
					$.getJSON(
						"<?php echo admin_url('admin-ajax.php'); ?>",
						{
							'action' : 'blc_full_status'
						},
						function (data, textStatus){
							if ( data && ( typeof(data['text']) != 'undefined' ) ){
								$('#wsblc_full_status').html(data.text);
							} else {
								$('#wsblc_full_status').html('<?php _e('[ Network error ]', 'broken-link-checker'); ?>');
							}
							
							setTimeout(blcUpdateStatus, 10000); //...update every 10 seconds							
						}
					);
				}
				blcUpdateStatus();//Call it the first time
				
			})(jQuery);
        </script>
        <?php //JHS: Recheck all posts link: ?>
        <p><input class="button" type="button" name="recheckbutton" 
				  value="<?php _e('Re-check all pages', 'broken-link-checker'); ?>" 
				  onclick="location.replace('<?php echo basename($_SERVER['PHP_SELF']); ?>?page=link-checker-settings&amp;recheck=true')" />
		</p>
        
        <table id="blc-debug-info">
        <?php
        
        //Output the debug info in a table
		foreach( $debug as $key => $value ){
			printf (
				'<tr valign="top" class="blc-debug-item-%s"><th scope="row">%s</th><td>%s<div class="blc-debug-message">%s</div></td></tr>',
				$value['state'],
				$key,
				$value['value'], 
				( array_key_exists('message', $value)?$value['message']:'')
			);
		}
        ?>
        </table>
        
        </td>
        </tr>

        <tr valign="top">
        <th scope="row"><?php _e('Check each link','broken-link-checker'); ?></th>
        <td>

		<?php
			printf( 
				__('Every %s hours','broken-link-checker'),
				sprintf(
					'<input type="text" name="check_threshold" id="check_threshold" value="%d" size="5" maxlength="5" />',
					$this->conf->options['check_threshold']
				)
			 ); 
		?>
        <br/>
        <span class="description">
        <?php _e('Existing links will be checked this often. New links will usually be checked ASAP.', 'broken-link-checker'); ?>
        </span>

        </td>
        </tr>

        <tr valign="top">
        <th scope="row"><?php _e('Broken link CSS','broken-link-checker'); ?></th>
        <td>
        	<label for='mark_broken_links'>
        		<input type="checkbox" name="mark_broken_links" id="mark_broken_links"
            	<?php if ($this->conf->options['mark_broken_links']) echo ' checked="checked"'; ?>/>
            	<?php _e('Apply <em>class="broken_link"</em> to broken links', 'broken-link-checker'); ?>
			</label>
			<br/>
        <textarea name="broken_link_css" id="broken_link_css" cols='45' rows='4'/><?php
            if( isset($this->conf->options['broken_link_css']) )
                echo $this->conf->options['broken_link_css'];
        ?></textarea>

        </td>
        </tr>
        
        <tr valign="top">
        <th scope="row"><?php _e('Removed link CSS','broken-link-checker'); ?></th>
        <td>
        	<label for='mark_removed_links'>
        		<input type="checkbox" name="mark_removed_links" id="mark_removed_links"
            	<?php if ($this->conf->options['mark_removed_links']) echo ' checked="checked"'; ?>/>
            	<?php _e('Apply <em>class="removed_link"</em> to unlinked links', 'broken-link-checker'); ?>
			</label>
			<br/>
        <textarea name="removed_link_css" id="removed_link_css" cols='45' rows='4'/><?php
            if( isset($this->conf->options['removed_link_css']) )
                echo $this->conf->options['removed_link_css'];
        ?></textarea>

        </td>
        </tr>

        <tr valign="top">
        <th scope="row"><?php _e('Exclusion list', 'broken-link-checker'); ?></th>
        <td><?php _e("Don't check links where the URL contains any of these words (one per line) :", 'broken-link-checker'); ?><br/>
        <textarea name="exclusion_list" id="exclusion_list" cols='45' rows='4' wrap='off'/><?php
            if( isset($this->conf->options['exclusion_list']) )
                echo implode("\n", $this->conf->options['exclusion_list']);
        ?></textarea>

        </td>
        </tr>
        
        <tr valign="top">
        <th scope="row"><?php _e('Custom fields', 'broken-link-checker'); ?></th>
        <td><?php _e('Check URLs entered in these custom fields (one per line) :', 'broken-link-checker'); ?><br/>
        <textarea name="blc_custom_fields" id="blc_custom_fields" cols='45' rows='4' /><?php
            if( isset($this->conf->options['custom_fields']) )
                echo implode("\n", $this->conf->options['custom_fields']);
        ?></textarea>

        </td>
        </tr>
        
        </table>
        
        <h3><?php _e('Advanced','broken-link-checker'); ?></h3>
        
        <table class="form-table">
        
        
        <tr valign="top">
        <th scope="row"><?php _e('Timeout', 'broken-link-checker'); ?></th>
        <td>

		<?php
		
		printf(
			__('%s seconds', 'broken-link-checker'),
			sprintf(
				'<input type="text" name="timeout" id="blc_timeout" value="%d" size="5" maxlength="3" />', 
				$this->conf->options['timeout']
			)
		);
		
		?>
        <br/><span class="description">
        <?php _e('Links that take longer than this to load will be marked as broken.','broken-link-checker'); ?> 
		</span>

        </td>
        </tr>
        
        
        <tr valign="top">
        <th scope="row">
			<a name='lockfile_directory'></a><?php _e('Custom temporary directory', 'broken-link-checker'); ?></th>
        <td>

        <input type="text" name="custom_tmp_dir" id="custom_tmp_dir"
            value="<?php echo htmlspecialchars( $this->conf->options['custom_tmp_dir'] ); ?>" size='53' maxlength='500'/>
            <?php 
            if ( !empty( $this->conf->options['custom_tmp_dir'] ) ) {
				if ( @is_dir( $this->conf->options['custom_tmp_dir'] ) ){
					if ( @is_writable( $this->conf->options['custom_tmp_dir'] ) ){
						echo "<strong>", __('OK', 'broken-link-checker'), "</strong>";
					} else {
						echo '<span class="error">';
						_e("Error : This directory isn't writable by PHP.", 'broken-link-checker');
						echo '</span>';
					}
				} else {
					echo '<span class="error">';
					_e("Error : This directory doesn't exist.", 'broken-link-checker');
					echo '</span>';
				}
			}
			
			?>
        <br/>
        <span class="description">
        <?php _e('Set this field if you want the plugin to use a custom directory for its lockfiles. Otherwise, leave it blank.','broken-link-checker'); ?>
        </span>

        </td>
        </tr>

        <tr valign="top">
        <th scope="row"><?php _e('Max. execution time', 'broken-link-checker'); ?></th>
        <td>

		<?php
		
		printf(
			__('%s seconds', 'broken-link-checker'),
			sprintf(
				'<input type="text" name="max_execution_time" id="max_execution_time" value="%d" size="5" maxlength="5" />', 
				$this->conf->options['max_execution_time']
			)
		);
		
		?>
        <br/><span class="description">
        <?php
        
        _e('The plugin works by periodically creating a background worker instance that parses your posts looking for links, checks the discovered URLs, and performs other time-consuming tasks. Here you can set for how long, at most, the background instance may run each time before stopping.', 'broken-link-checker');
		
		?> 
		</span>

        </td>
        </tr>
        
        </table>
        
        <p class="submit"><input type="submit" name="submit" class='button-primary' value="<?php _e('Save Changes') ?>" /></p>
        </form>
        </div>
        
        <script type='text/javascript'>
        	jQuery(function($){
        		var toggleButton = $('#blc-debug-info-toggle'); 
        		
				toggleButton.click(function(){
					
					var box = $('#blc-debug-info'); 
					box.toggle();
					if( box.is(':visible') ){
						toggleButton.text('<?php _e('Hide debug info', 'broken-link-checker'); ?>');
					} else {
						toggleButton.text('<?php _e('Show debug info', 'broken-link-checker'); ?>');
					}
					
				});
			});
		</script>
        <?php
    }
    
    function options_page_css(){
    	?>
		<style type='text/css'>
			#blc-debug-info-toggle {
				font-size: smaller;
			}
		
        	.blc-debug-item-ok {
				background-color: #d7ffa2;
			}
        	.blc-debug-item-warning {
				background-color: #fcffa2;
			}
	        .blc-debug-item-error {
				background-color: #ffc4c4;
			}
			
			#blc-debug-info {
				display: none;
				
				text-align: left;
				
				border-width: 1px;
				border-color: gray;
				border-style: solid;
				
				border-spacing: 0px;
				border-collapse: collapse;
			}
			
			#blc-debug-info th, #blc-debug-info td {
				padding: 6px;
				font-weight: normal;
				text-shadow: none;
								
				border-width: 1px ;
				border-color: silver;
				border-style: solid;
				
				border-collapse: collapse;
			}
		</style>
		<?php
	}
	
  /**
   * wsBrokenLinkChecker::init_native_filters()
   * Initializes (if necessary) and returns the list of built-in link filters
   *
   * @return array
   */
	function init_native_filters(){
		if ( !empty($this->native_filters) ){
			return $this->native_filters;
		} else {
			//Available filters by link type + the appropriate WHERE expressions
			$this->native_filters = array(
				'broken' => array(
					'where_expr' => '( http_code < 200 OR http_code >= 400 OR timeout = 1 ) AND ( check_count > 0 ) AND ( http_code <> ' . BLC_CHECKING . ')',
					'name' => __('Broken', 'broken-link-checker'),
					'heading' => __('Broken Links', 'broken-link-checker'),
					'heading_zero' => __('No broken links found', 'broken-link-checker')
				 ), 
				 'redirects' => array(
					'where_expr' => '( redirect_count > 0 )',
					'name' => __('Redirects', 'broken-link-checker'),
					'heading' => __('Redirected Links', 'broken-link-checker'),
					'heading_zero' => __('No redirects found', 'broken-link-checker')
				 ), 
				 
				'all' => array(
					'where_expr' => '1',
					'name' => __('All', 'broken-link-checker'),
					'heading' => __('Detected Links', 'broken-link-checker'),
					'heading_zero' => __('No links found (yet)', 'broken-link-checker')
				 ), 
			);
			
			return $this->native_filters;
		}
	}
	
  /**
   * wsBrokenLinkChecker::get_custom_filters()
   * Returns a list of user-defined link filters.
   *
   * @return array An array of custom filter definitions. If there are no custom filters defined returns an empty array.
   */
	function get_custom_filters(){
		global $wpdb;
		
		$filter_data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}blc_filters ORDER BY name ASC", ARRAY_A);
		$filters = array();
		
		if ( !empty($filter_data) ) {		
			foreach($filter_data as $data){
				$filters[ 'f'.$data['id'] ] = array(
					'name' => $data['name'],
					'params' => $data['params'],
					'is_search' => true,
					'heading' => ucwords($data['name']),
					'heading_zero' => __('No links found for your query', 'broken-link-checker'),
				);
			}
		}
		
		return $filters;
	}
	
	function get_search_params( $filter = null ){
		//If present, the filter's parameters may be saved either as an array or a string.
		$params = array();
		if ( !empty($filter) && !empty($filter['params']) ){
			$params = $filter['params']; 
			if ( is_string( $params ) ){
				wp_parse_str($params, $params);
			}
		} else {
			//If the filter doesn't have it's own search query, use the URL parameters
			$params = array_merge($params, $_GET);
		}
		
		//Only leave valid search query parameters
		$search_param_names = array( 's_link_text', 's_link_url', 's_http_code', 's_filter', 's_link_type' );
		$output = array();
		foreach ( $params as $name => $value ){
			if ( in_array($name, $search_param_names) ){
				$output[$name] = $value;
			}
		}
		
		return $output;
	}

    function links_page(){
        global $wpdb;
        
        $action = !empty($_POST['action'])?$_POST['action']:'';
        if ( intval($action) == -1 ){
        	//Try the second bulk actions box
			$action = !empty($_POST['action2'])?$_POST['action2']:'';
		}
        
        //Get the list of link IDs selected via checkboxes
        $selected_links = array();
		if ( isset($_POST['selected_links']) && is_array($_POST['selected_links']) ){
			//Convert all link IDs to integers (non-numeric entries are converted to zero)
			$selected_links = array_map('intval', $_POST['selected_links']);
			//Remove all zeroes
			$selected_links = array_filter($selected_links);
		}
        
        $message = '';
        $msg_class = 'updated';
        
        if ( $action == 'create-custom-filter' ){
        	//Create a custom filter!
        	
        	check_admin_referer( $action );
        	
        	//Filter name must be set
			if ( empty($_POST['name']) ){
				$message = __("You must enter a filter name!", 'broken-link-checker');
				$msg_class = 'error';
			//Filter parameters (a search query) must also be set
			} elseif ( empty($_POST['params']) ){
				$message = __("Invalid search query.", 'broken-link-checker');
				$msg_class = 'error';
			} else {
				//Save the new filter
				$q = $wpdb->prepare(
					"INSERT INTO {$wpdb->prefix}blc_filters(name, params) VALUES (%s, %s)",
					$_POST['name'], $_POST['params']
				);
				
				if ( $wpdb->query($q) ){
					//Saved
					$message = sprintf( __('Filter "%s" created', 'broken-link-checker'), $_POST['name']);
					//A little hack to make the filter active immediately
					$_GET['filter_id'] = 'f' . $wpdb->insert_id;			
				} else {
					//Error
					$message = sprintf( __("Database error : %s", 'broken-link-checker'), $wpdb->last_error);
					$msg_class = 'error';
				}
			}
			
		} elseif ( $action == 'delete-custom-filter' ){
			//Delete an existing custom filter!
			
			check_admin_referer( $action );
			
			//Filter ID must be set
			if ( empty($_POST['filter_id']) ){
				$message = __("Filter ID not specified.", 'broken-link-checker');
				$msg_class = 'error';
			} else {
				//Remove the "f" character from the filter ID to get its database key
				$filter_id = intval(ltrim($_POST['filter_id'], 'f'));
				//Try to delete the filter
				$q = $wpdb->prepare("DELETE FROM {$wpdb->prefix}blc_filters WHERE id = %d", $filter_id);
				if ( $wpdb->query($q) ){
					//Success
					$message = __('Filter deleted', 'broken-link-checker');
				} else {
					//Either the ID is wrong or there was some other error
					$message = __('Database error : %s', 'broken-link-checker');
					$msg_class = 'error';
				}
			}
			
		} elseif ($action == 'bulk-delete-sources') {
			//Delete posts and blogroll entries that contain any of the selected links
			//(links inside custom fields count as part of the post for the purposes of this action).
			//
			//Note that once all posts/bookmarks containing a particular link have been deleted,
			//there is no need to explicitly delete the link record itself. The hooks attached to 
			//the post_deleted and delete_link actions will take care of that. 
						
			check_admin_referer( 'bulk-action' );
			
			if ( count($selected_links) > 0 ) {	
				$selected_links_sql = implode(', ', $selected_links);	
				
				$messages = array();	
								
				//First, fetch the posts that contain any of the selected links,
				//either in the content or in a custom field.
				$q = "
					SELECT posts.id, posts.post_title
					FROM 
						{$wpdb->prefix}blc_links AS links,
						{$wpdb->prefix}blc_instances AS instances,
						{$wpdb->posts} AS posts
					WHERE
						links.link_id IN ($selected_links_sql)
						AND links.link_id = instances.link_id
						AND (instances.source_type = 'post' OR instances.source_type = 'custom_field')
						AND instances.source_id = posts.id
						AND posts.post_status <> \"trash\"
					GROUP BY posts.id
				";
				
				$posts_to_delete = $wpdb->get_results($q);
				$deleted_posts = array();
				
				//Delete the selected posts
				foreach($posts_to_delete as $post){
					if ( wp_delete_post($post->id) !== false) {
						$deleted_posts[] = $post;
					} else {
						$messages[] = sprintf(
							__('Failed to delete post "%s" (%d)', 'broken-link-checker'),
							$post->pots_title,
							$post->id
						);
						$msg_class = 'error';
					};
				}
								
				if ( count($deleted_posts) > 0 ) {
					//Since the "Trash" feature has been introduced, calling wp_delete_post
					//doesn't actually delete the post (unless you set force_delete to True), 
					//just moves it to the trash. So we pick the message accordingly. 
					if ( function_exists('wp_trash_post') ){
						$delete_msg = _n("%d post moved to the trash", "%d posts moved to the trash", count($deleted_posts), 'broken-link-checker');
					} else {
						$delete_msg = _n("%d post deleted", "%d posts deleted", count($deleted_posts), 'broken-link-checker');
					}
					
					$messages[] = sprintf( 
						$delete_msg, 
						count($deleted_posts)
					);
				}
				
				//Fetch blogroll links (AKA bookmarks) that match any of the selected links
				$q = "
					SELECT bookmarks.link_id AS bookmark_id, bookmarks.link_name
					FROM 
						{$wpdb->prefix}blc_links AS links,
						{$wpdb->prefix}blc_instances AS instances,
						{$wpdb->links} AS bookmarks
					WHERE
						links.link_id IN ($selected_links_sql)
						AND links.link_id = instances.link_id
						AND instances.source_type = 'blogroll'
						AND instances.source_id = bookmarks.link_id
					GROUP BY bookmarks.link_id
				";
				//echo "<pre>$q</pre>";
				
				$bookmarks_to_delete = $wpdb->get_results($q);
				$deleted_bookmarks = array();
				
				if ( count($bookmarks_to_delete) > 0 ){
					//Delete the matching blogroll links
					foreach($bookmarks_to_delete as $bookmark){
						if ( wp_delete_link($bookmark->bookmark_id) ){
							$deleted_bookmarks[] = $bookmark;
						} else {
							$messages[] = sprintf(
								__('Failed to delete blogroll link "%s" (%d)', 'broken-link-checker'),
								$bookmark->link_name,
								$bookmark->link_id
							);
							$msg_class = 'error';
						}
					}
					
					if ( count($deleted_bookmarks) > 0 ) {
						$messages[] = sprintf( 
							_n("%d blogroll link deleted", "%d blogroll links deleted", count($deleted_bookmarks), 'broken-link-checker'), 
							count($deleted_bookmarks)
						);
					}
				}
				
				if ( count($messages) > 0 ){
					$message = implode('<br>', $messages);
				} else {
					$message = __("Didn't find anything to delete!", 'broken-link-checker');
					$msg_class = 'error';
				}
				
				
			}
		
		} elseif ($action == 'bulk-unlink') {
			//Unlink all selected links.
			
			check_admin_referer( 'bulk-action' );
			
			if ( count($selected_links) > 0 ) {	
				$selected_links_sql = implode(', ', $selected_links);
				
				//Fetch the selected links
				$q = "SELECT * FROM {$wpdb->prefix}blc_links WHERE link_id IN ($selected_links_sql)";				
				$links = $wpdb->get_results($q, ARRAY_A);
				
				if ( count($links) > 0 ) {
					$processed_links = 0;
					$failed_links = 0;
					
					//Unlink (delete) all selected links
					foreach($links as $link){
						$the_link = new blcLink($link);
						$rez = $the_link->unlink();
						if ( $rez !== false ){
							$processed_links++;
						} else {
							$failed_links++;
						}
					}	
					
					//This message is slightly misleading - it doesn't account for the fact that 
					//a link can be present in more than one post.
					$message = sprintf(
						_n(
							'%d link removed',
							'%d links removed',
							$processed_links, 
							'broken-link-checker'
						),
						$processed_links
					);			
					
					if ( $failed_links > 0 ) {
						$message .= '<br>' . sprintf(
							_n(
								'Failed to remove %d link', 
								'Failed to remove %d links',
								$failed_links,
								'broken-link-checker'
							),
							$failed_links
						);
						$msg_class = 'error';
					}
				}
			}
			
		} elseif ($action == 'bulk-deredirect') {
			//For all selected links, replace the URL with the final URL that it redirects to.
			
			check_admin_referer( 'bulk-action' );
			
			if ( count($selected_links) > 0 ) {	
				$selected_links_sql = implode(', ', $selected_links);
				
				//Fetch the selected links
				$q = "SELECT * FROM {$wpdb->prefix}blc_links WHERE link_id IN ($selected_links_sql) AND redirect_count > 0";				
				$links = $wpdb->get_results($q, ARRAY_A);
				
				if ( count($links) > 0 ) {
					$processed_links = 0;
					$failed_links = 0;
					
					//Deredirect all selected links
					foreach($links as $link){
						$the_link = new blcLink($link);
						$rez = $the_link->deredirect();
						if ( $rez !== false ){
							$processed_links++;
						} else {
							$failed_links++;
						}
					}	
					
					$message = sprintf(
						_n(
							'Replaced %d redirect with a direct link',
							'Replaced %d redirects with direct links',
							$processed_links, 
							'broken-link-checker'
						),
						$processed_links
					);			
					
					if ( $failed_links > 0 ) {
						$message .= '<br>' . sprintf(
							_n(
								'Failed to fix %d redirect', 
								'Failed to fix %d redirects',
								$failed_links,
								'broken-link-checker'
							),
							$failed_links
						);
						$msg_class = 'error';
					}
				} else {
					$message = __('None of the selected links are redirects!', 'broken-link-checker');
				}
			}
			
		}
		
		if ( !empty($message) ){
			echo '<div id="message" class="'.$msg_class.' fade"><p><strong>'.$message.'</strong></p></div>';
		}
		
        //Build the filter list
		$filters = array_merge($this->native_filters, $this->get_custom_filters());
		
		//Add the special "search" filter
		$filters['search'] = array(
			'name' => __('Search', 'broken-link-checker'),
			'heading' => __('Search Results', 'broken-link-checker'),
			'heading_zero' => __('No links found for your query', 'broken-link-checker'),
			'is_search' => true,
			'where_expr' => 1,
			'hidden' => true,
		);
		
		//Calculate the number of links for each filter
		foreach ($filters as $filter => $data){
			$filters[$filter]['count'] = $this->get_links($data, 0, 0, true);
		}

		//Get the selected filter (defaults to displaying broken links)
		$filter_id = isset($_GET['filter_id'])?$_GET['filter_id']:'broken';
		if ( !isset($filters[$filter_id]) ){
			$filter_id = 'broken';
		}
		
		//Get the desired page number (must be > 0) 
		$page = isset($_GET['paged'])?intval($_GET['paged']):'1';
		if ($page < 1) $page = 1;
		
		//Links per page [1 - 200]
		$per_page = isset($_GET['per_page'])?intval($_GET['per_page']):'30';
		if ($per_page < 1){
			$per_page = 30;
		} else if ($per_page > 200){
			$per_page = 200;
		}
		
		$current_filter = $filters[$filter_id];
		$max_pages = ceil($current_filter['count'] / $per_page);
		
		//Select the required links + 1 instance per link.
		$links = $this->get_links( $current_filter, ( ($page-1) * $per_page ), $per_page );
		if ( is_null($links) && !empty($wpdb->last_error) ){
			printf( __('Database error : %s', 'broken-link-checker'), $wpdb->last_error);
		}
		
		//Save the search params (if any) in a handy array for later
		if ( !empty($current_filter['is_search']) ){
			$search_params = $this->get_search_params($current_filter);
		} else {
			$search_params = array();
		}
		
		//Display the "Discard" button when listing broken links
		$show_discard_button = ('broken' == $filter_id) || (!empty($search_params['s_filter']) && ($search_params['s_filter'] == 'broken'));
		
		//Figure out what the "safe" URL to acccess the current page would be.
		//This is used by the bulk action form. 
		$special_args = array('_wpnonce', '_wp_http_referer', 'action', 'selected_links');
		$neutral_current_url = remove_query_arg($special_args);
		
        ?>
        
<script type='text/javascript'>
	var blc_current_filter = '<?php echo $filter_id; ?>';
</script>
        
<div class="wrap">
<h2><?php
	//Output a header matching the current filter
	if ( $current_filter['count'] > 0 ){
		echo $current_filter['heading'] . " (<span class='current-link-count'>{$current_filter[count]}</span>)";
	} else {
		echo $current_filter['heading_zero'] . "<span class='current-link-count'></span>";
	}
?>
</h2>
	<ul class="subsubsub">
    	<?php
    		//Construct a submenu of filter types
    		$items = array();
			foreach ($filters as $filter => $data){
				if ( !empty($data['hidden']) ) continue; //skip hidden filters
																
				$class = $number_class = '';
				
				if ( $filter_id == $filter ) {
					$class = 'class="current"';
					$number_class = 'current-link-count';	
				}
				
				$items[] = "<li><a href='tools.php?page=view-broken-links&filter_id=$filter' $class>
					{$data[name]}</a> <span class='count'>(<span class='$number_class'>{$data[count]}</span>)</span>";
			}
			echo implode(' |</li>', $items);
			unset($items);
		?>
	</ul>
	
<div class="search-box">
	
	<?php
			//If we're currently displaying search results offer the user the option to s
			//save the search query as a custom filter. 	
			if ( $filter_id == 'search' ){
	?>
	<form name="save-search-query" id="custom-filter-form" action="<?php echo admin_url("tools.php?page=view-broken-links");  ?>" method="post" class="blc-inline-form">
		<?php wp_nonce_field('create-custom-filter');  ?>
		<input type="hidden" name="name" id="blc-custom-filter-name" value="" />
		<input type="hidden" name="params" id="blc-custom-filter-params" value="<?php echo http_build_query($search_params, null, '&'); ?>" />
		<input type="hidden" name="action" value="create-custom-filter" />
		<input type="button" value="<?php esc_attr_e( 'Save This Search As a Filter', 'broken-link-checker' ); ?>" id="blc-create-filter" class="button" />
	</form>				 				
	<?php
			} elseif ( !empty($current_filter['is_search']) ){
			//If we're displaying a custom filter give an option to delete it.
	?>
	<form name="save-search-query" id="custom-filter-form" action="<?php echo admin_url("tools.php?page=view-broken-links");  ?>" method="post" class="blc-inline-form">
		<?php wp_nonce_field('delete-custom-filter');  ?>
		<input type="hidden" name="filter_id" id="blc-custom-filter-id" value="<?php echo $filter_id; ?>" />
		<input type="hidden" name="action" value="delete-custom-filter" />
		<input type="submit" value="<?php esc_attr_e( 'Delete This Filter', 'broken-link-checker' ); ?>" id="blc-delete-filter" class="button" />
	</form>
	<?php
			}
	?>
	
	<input type="button" value="<?php esc_attr_e( 'Search', 'broken-link-checker' ); ?> &raquo;" id="blc-open-search-box" class="button" />
</div>

<!-- The search dialog -->
<div id='search-links-dialog' title='Search'>
<form class="search-form" action="<?php echo admin_url('tools.php?page=view-broken-links'); ?>" method="get">
	<input type="hidden" name="page" value="view-broken-links" />
	<input type="hidden" name="filter_id" value="search" />
	<fieldset>
	
	<label for="s_link_text"><?php _e('Link text', 'broken-link-checker'); ?></label>
	<input type="text" name="s_link_text" value="<?php if(!empty($search_params['s_link_text'])) echo esc_attr($search_params['s_link_text']); ?>" id="s_link_text" class="text ui-widget-content" />
	
	<label for="s_link_url"><?php _e('URL', 'broken-link-checker'); ?></label>
	<input type="text" name="s_link_url" id="s_link_url" value="<?php if(!empty($search_params['s_link_url'])) echo esc_attr($search_params['s_link_url']); ?>" class="text ui-widget-content" />
	
	<label for="s_http_code"><?php _e('HTTP code', 'broken-link-checker'); ?></label>
	<input type="text" name="s_http_code" id="s_http_code" value="<?php if(!empty($search_params['s_http_code'])) echo esc_attr($search_params['s_http_code']); ?>" class="text ui-widget-content" />
	
	<label for="s_filter"><?php _e('Link status', 'broken-link-checker'); ?></label>
	<select name="s_filter" id="s_filter">
		<?php
		if ( !empty($search_params['s_filter']) ){
			$search_subfilter = $search_params['s_filter']; 
		} else {
			$search_subfilter = $filter_id;
		}
		
		foreach ($this->native_filters as $filter => $data){
			$selected = ($search_subfilter == $filter)?' selected="selected"':'';
			printf('<option value="%s"%s>%s</option>', $filter, $selected, $data['name']);
		}		 
		?>
	</select>
	
	<label for="s_link_type"><?php _e('Link type', 'broken-link-checker'); ?></label>
	<select name="s_link_type" id="s_link_type">
		<?php
		$link_types = array(
			__('Any', 'broken-link-checker') => '',
			__('Normal link', 'broken-link-checker') => 'link',
			__('Image', 'broken-link-checker') => 'image',
			__('Custom field', 'broken-link-checker') => 'custom_field',
			__('Bookmark', 'broken-link-checker') => 'blogroll',
		);
		
		foreach ($link_types as $name => $value){
			$selected = ( isset($search_params['s_link_type']) && $search_params['s_link_type'] == $value )?' selected="selected"':'';
			printf('<option value="%s"%s>%s</option>', $value, $selected, $name);
		}
		?>
	</select>
	
	</fieldset>
	
	<div id="blc-search-button-row">
		<input type="submit" value="<?php esc_attr_e( 'Search Links', 'broken-link-checker' ); ?>" id="blc-search-button" name="search_button" class="button-primary" />
		<input type="button" value="<?php esc_attr_e( 'Cancel', 'broken-link-checker' ); ?>" id="blc-cancel-search" class="button" />
	</div>
	
</form>	
</div>

<?php
		//Do we have any links to display?
        if( $links && ( count($links) > 0 ) ) {
?>
<!-- The link list -->
<form id="blc-bulk-action-form" action="<?php echo $neutral_current_url;  ?>" method="post">
	<?php 
		wp_nonce_field('bulk-action');
		
		$bulk_actions = array(
			'-1' => __('Bulk Actions', 'broken-link-checker'),
			"bulk-unlink" => __('Unlink', 'broken-link-checker'),
			"bulk-deredirect" => __('Fix redirects', 'broken-link-checker'),
			"bulk-delete-sources" => __('Delete sources', 'broken-link-checker'),
		);
		
		$bulk_actions_html = '';
		foreach($bulk_actions as $value => $name){
			$bulk_actions_html .= sprintf('<option value="%s">%s</option>', $value, $name);
		} 
	?>

	<div class='tablenav'>
		<div class="alignleft actions">
			<select name="action">
				<?php echo $bulk_actions_html; ?>
			</select>
			<input type="submit" name="doaction" id="doaction" value="<?php echo attribute_escape(__('Apply', 'broken-link-checker')); ?>" class="button-secondary action">
		</div>
		<?php
			//Display pagination links 
			$page_links = paginate_links( array(
				'base' => add_query_arg( 'paged', '%#%' ),
				'format' => '',
				'prev_text' => __('&laquo;'),
				'next_text' => __('&raquo;'),
				'total' => $max_pages,
				'current' => $page
			));
			
			if ( $page_links ) { 
				echo '<div class="tablenav-pages">';
				$page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of <span class="current-link-count">%s</span>', 'broken-link-checker' ) . '</span>%s',
					number_format_i18n( ( $page - 1 ) * $per_page + 1 ),
					number_format_i18n( min( $page * $per_page, $current_filter['count'] ) ),
					number_format_i18n( $current_filter['count'] ),
					$page_links
				); 
				echo $page_links_text; 
				echo '</div>';
			}
		?>
	
	</div>
            <table class="widefat" id="blc-links">
                <thead>
                <tr>

				<th scope="col" id="cb" class="check-column">
					<input type="checkbox">
				</th>
                <th scope="col"><?php _e('Source', 'broken-link-checker'); ?></th>
                <th scope="col"><?php _e('Link Text', 'broken-link-checker'); ?></th>
                <th scope="col"><?php _e('URL', 'broken-link-checker'); ?></th>

				<?php if ( $show_discard_button ) { ?> 
                <th scope="col"> </th>
                <?php } ?>

                </tr>
                </thead>
                <tbody id="the-list">
            <?php
            $rowclass = ''; $rownum = 0;
            foreach ($links as $link) {
            	$rownum++;
            	
            	$rowclass = 'alternate' == $rowclass ? '' : 'alternate';
            	$excluded = $this->is_excluded( $link['url'] ); 
            	if ( $excluded ) $rowclass .= ' blc-excluded-link';
            	
                ?>
                <tr id='<?php echo "blc-row-$rownum"; ?>' class='blc-row <?php echo $rowclass; ?>'>
                
				<th class="check-column" scope="row">
					<input type="checkbox" name="selected_links[]" value="<?php echo $link['link_id']; ?>">
				</th>
				                
                <td class='post-title column-title'>
                	<span class='blc-link-id' style='display:none;'><?php echo $link['link_id']; ?></span> 	
                  <?php 
				  if ( ('post' == $link['source_type']) || ('custom_field' == $link['source_type']) ){
				  	 
                  	echo "<a class='row-title' href='post.php?action=edit&amp;post=$link[source_id]' title='", 
					  	attribute_escape(__('Edit this post')),
						 "'>{$link[post_title]}</a>";

					//Output inline action links (copied from edit-post-rows.php)                  	
                  	$actions = array();
					if ( current_user_can('edit_post', $link['source_id']) ) {
						$actions['edit'] = '<span class="edit"><a href="' . get_edit_post_link($link['source_id'], true) . '" title="' . attribute_escape(__('Edit this post')) . '">' . __('Edit') . '</a>';
						$actions['delete'] = "<span class='delete'><a class='submitdelete' title='" . attribute_escape(__('Delete this post')) .  "' href='" . wp_nonce_url("post.php?action=delete&amp;post=".$link['source_id'], 'delete-post_' . $link['source_id']) . "' onclick=\"if ( confirm('" . js_escape(sprintf( __("You are about to delete this post '%s'\n 'Cancel' to stop, 'OK' to delete."), $link['post_title'] )) . "') ) { return true;}return false;\">" . __('Delete') . "</a>";
					}
					$actions['view'] = '<span class="view"><a href="' . get_permalink($link['source_id']) . '" title="' . attribute_escape(sprintf(__('View "%s"', 'broken-link-checker'), $link['post_title'])) . '" rel="permalink">' . __('View') . '</a>';
					echo '<div class="row-actions">';
					echo implode(' | </span>', $actions);
					echo '</div>';
					
                  } elseif ( 'blogroll' == $link['source_type'] ) {
                  	
                  	echo "<a class='row-title' href='link.php?action=edit&amp;link_id=$link[source_id]' title='" . __('Edit this bookmark', 'broken-link-checker') . "'>{$link[link_text]}</a>";
                  	
                  	//Output inline action links                  	
                  	$actions = array();
					if ( current_user_can('manage_links') ) {
						$actions['edit'] = '<span class="edit"><a href="link.php?action=edit&amp;link_id=' . $link['source_id'] . '" title="' . attribute_escape(__('Edit this bookmark', 'broken-link-checker')) . '">' . __('Edit') . '</a>';
						$actions['delete'] = "<span class='delete'><a class='submitdelete' href='" . wp_nonce_url("link.php?action=delete&amp;link_id={$link[source_id]}", 'delete-bookmark_' . $link['source_id']) . "' onclick=\"if ( confirm('" . js_escape(sprintf( __("You are about to delete this link '%s'\n  'Cancel' to stop, 'OK' to delete."), $link['link_text'])) . "') ) { return true;}return false;\">" . __('Delete') . "</a>";
					}
					
					echo '<div class="row-actions">';
					echo implode(' | </span>', $actions);
					echo '</div>';
                  	
				  } elseif ( empty($link['source_type']) ){
				  	
					_e("[An orphaned link! This is a bug.]", 'broken-link-checker');
					
				  }
				  	?>
				</td>
                <td class='blc-link-text'><?php
                if ( 'post' == $link['source_type'] ){
                	
					if ( 'link' == $link['instance_type'] ) {	 
						print strip_tags($link['link_text']);
					} elseif ( 'image' == $link['instance_type'] ){
						printf(
							'<img src="%s/broken-link-checker/images/image.png" class="blc-small-image" alt="%2$s" title="%2$s"> %2$s',
							WP_PLUGIN_URL,
							__('Image', 'broken-link-checker')
						);
					} else {
						echo '[ ??? ]';
					}
						
				} elseif ( 'custom_field' == $link['source_type'] ){
					
					printf(
						'<img src="%s/broken-link-checker/images/script_code.png" class="blc-small-image" title="%2$s" alt="%2$s"> ',
						WP_PLUGIN_URL,
						__('Custom field', 'broken-link-checker')
					);
					echo "<code>".$link['link_text']."</code>";
					
				} elseif ( 'blogroll' == $link['source_type'] ){
					printf(
						'<img src="%s/broken-link-checker/images/link.png" class="blc-small-image" title="%2$s" alt="%2$s"> %2$s',
						WP_PLUGIN_URL,
						__('Bookmark', 'broken-link-checker')						
					);
				}
				?>
				</td>
                <td class='column-url'>
                    <a href='<?php print $link['url']; ?>' target='_blank' class='blc-link-url'>
                    	<?php print $this->mytruncate($link['url']); ?></a>
                    <input type='text' id='link-editor-<?php print $rownum; ?>' 
                    	value='<?php print attribute_escape($link['url']); ?>'
                        class='blc-link-editor' style='display:none' />
                <?php
                	//Output inline action links for the link/URL                  	
                  	$actions = array();
                  	
					$actions['details'] = "<span class='view'><a class='blc-details-button' href='javascript:void(0)' title='". attribute_escape(__('Show more info about this link', 'broken-link-checker')) . "'>". __('Details', 'broken-link-checker') ."</a>";
                  	
					$actions['delete'] = "<span class='delete'><a class='submitdelete blc-unlink-button' title='" . attribute_escape( __('Remove this link from all posts', 'broken-link-checker') ). "' ".
						"id='unlink-button-$rownum' href='javascript:void(0);'>" . __('Unlink', 'broken-link-checker') . "</a>";
					
					if ( $excluded ){
						$actions['exclude'] = "<span class='delete'>" . __('Excluded', 'broken-link-checker');
					} else {
						$actions['exclude'] = "<span class='delete'><a class='submitdelete blc-exclude-button' title='" . attribute_escape( __('Add this URL to the exclusion list' , 'broken-link-checker') ) . "' ".
							"id='exclude-button-$rownum' href='javascript:void(0);'>" . __('Exclude' , 'broken-link-checker'). "</a>";
					}
					
					$actions['edit'] = "<span class='edit'><a href='javascript:void(0)' class='blc-edit-button' title='" . attribute_escape( __('Edit link URL' , 'broken-link-checker') ) . "'>". __('Edit URL' , 'broken-link-checker') ."</a>";
						
					echo '<div class="row-actions">';
					echo implode(' | </span>', $actions);
					
					echo "<span style='display:none' class='blc-cancel-button-container'> ",
						 "| <a href='javascript:void(0)' class='blc-cancel-button' title='". attribute_escape(__('Cancel URL editing' , 'broken-link-checker')) ."'>". __('Cancel' , 'broken-link-checker') ."</a></span>";
					   	
					echo '</div>';
                ?>
                </td>
                <?php
				 	//Display the "Discard" button when listing broken links
					if ( $show_discard_button ) { 
				?> 
				<td><a href='javascript:void(0);'  
					id='discard_button-<?php print $rownum; ?>'
					class='blc-discard-button'
					title='<?php
						echo attribute_escape( 
							__('Remove this link from the list of broken links and mark it as valid', 'broken-link-checker')
						); 
					?>'><?php _e('Discard', 'broken-link-checker'); ?></a>
				</td>
                <?php } ?>
                </tr>
                <!-- Link details -->
                <tr id='<?php print "link-details-$rownum"; ?>' style='display:none;' class='blc-link-details'>
					<td colspan='<?php echo $show_discard_button?5:4; ?>'><?php $this->link_details_row($link); ?></td>
				</tr><?php
            }
            ?></tbody></table>
            
	<div class="tablenav">			
		<div class="alignleft actions">
			<select name="action2">
				<?php echo $bulk_actions_html; ?>
			</select>
			<input type="submit" name="doaction2" id="doaction2" value="<?php echo attribute_escape(__('Apply', 'broken-link-checker')); ?>" class="button-secondary action">
		</div><?php
            
            //Also display pagination links at the bottom
            if ( $page_links ) {
				echo '<div class="tablenav-pages">';
				$page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of <span class="current-link-count">%s</span>', 'broken-link-checker' ) . '</span>%s',
					number_format_i18n( ( $page - 1 ) * $per_page + 1 ),
					number_format_i18n( min( $page * $per_page, $current_filter['count'] ) ),
					number_format_i18n( $current_filter['count'] ),
					$page_links
				); 
				echo $page_links_text; 
				echo '</div>';
			}
?>
	</div>
	
</form>
<?php

        }; //End of the links table & assorted nav stuff
        
?>

		<?php $this->links_page_js(); ?>
</div>
        <?php
    } //Function ends
    
    function links_page_js(){
		?>
<script type='text/javascript'>

function alterLinkCounter(factor){
    cnt = parseInt(jQuery('.current-link-count').eq(0).html());
    cnt = cnt + factor;
    jQuery('.current-link-count').html(cnt);
}

jQuery(function($){
	
	//The discard button - manually mark the link as valid. The link will be checked again later.
	$(".blc-discard-button").click(function () {
		var me = this;
		$(me).html('<?php echo js_escape(__('Wait...', 'broken-link-checker')); ?>');
		
		var link_id = $(me).parents('.blc-row').find('.blc-link-id').html();
        
        $.post(
			"<?php echo admin_url('admin-ajax.php'); ?>",
			{
				'action' : 'blc_discard',
				'link_id' : link_id
			},
			function (data, textStatus){
				if (data == 'OK'){
					var master = $(me).parents('.blc-row'); 
					var details = master.next('.blc-link-details'); 
					
					details.hide();
					//Flash the main row green to indicate success, then hide it.
					var oldColor = master.css('background-color');
					master.animate({ backgroundColor: "#E0FFB3" }, 200).animate({ backgroundColor: oldColor }, 300, function(){
						master.hide();
					});
					
                    alterLinkCounter(-1);
				} else {
					$(me).html('<?php echo js_escape(__('Discard' , 'broken-link-checker'));  ?>');
					alert(data);
				}
			}
		);
    });
    
    //The details button - display/hide detailed info about a link
    $(".blc-details-button, .blc-link-text").click(function () {
    	$(this).parents('.blc-row').next('.blc-link-details').toggle();
    });
    
    //The edit button - edit/save the link's URL
    $(".blc-edit-button").click(function () {
		var edit_button = $(this);
		var master = $(edit_button).parents('.blc-row');
		var editor = $(master).find('.blc-link-editor');
		var url_el = $(master).find('.blc-link-url');
		var cancel_button_container = $(master).find('.blc-cancel-button-container');
		
      	//Find the current/original URL
    	var orig_url = url_el.attr('href');
    	//Find the link ID
    	var link_id = $(master).find('.blc-link-id').html();
    	
        if ( !$(editor).is(':visible') ){
        	//Begin editing
        	url_el.hide();
        	//Reset the edit box to the actual URL value in case the user has already tried and failed to edit this link.
        	editor.val( url_el.attr('href') );  
            editor.show();
            cancel_button_container.show();
            editor.focus();
            editor.select();
            edit_button.html('<?php echo js_escape(__('Save URL' , 'broken-link-checker')); ?>');
        } else {
            editor.hide();
            cancel_button_container.hide();
			url_el.show();
			
            new_url = editor.val();
            
            if (new_url != orig_url){
                //Save the changed link
                url_el.html('<?php echo js_escape(__('Saving changes...' , 'broken-link-checker')); ?>');
                
                $.getJSON(
					"<?php echo admin_url('admin-ajax.php'); ?>",
					{
						'action' : 'blc_edit',
						'link_id' : link_id,
						'new_url' : new_url
					},
					function (data, textStatus){
						var display_url = '';
						
						if ( data && (typeof(data['error']) != 'undefined') ){
							//data.error is an error message
							alert(data.error);
							display_url = orig_url;
						} else {
							//data contains info about the performed edit
							if ( data.cnt_okay > 0 ){
								display_url = new_url;
								
								url_el.attr('href', new_url);
								
								if ( data.cnt_error > 0 ){
									//TODO: Internationalize this error message
									var msg = "The link was successfully modifed.";
									msg = msg + "\nHowever, "+data.cnt_error+" instances couldn't be edited and still point to the old URL."
									alert(msg);
								} else {
									//Flash the row green to indicate success
									var oldColor = master.css('background-color');
									master.animate({ backgroundColor: "#E0FFB3" }, 200).animate({ backgroundColor: oldColor }, 300);
									
									//Save the new ID 
									master.find('.blc-link-id').html(data.new_link_id);
									//Load up the new link info                     (so sue me)    
									master.next('.blc-link-details').find('td').html('<center><?php echo js_escape(__('Loading...' , 'broken-link-checker')); ?></center>').load(
										"<?php echo admin_url('admin-ajax.php'); ?>",
										{
											'action' : 'blc_link_details',
											'link_id' : data.new_link_id
										}
									);
								}
							} else {
								//TODO: Internationalize this error message
								alert("Something went wrong. The plugin failed to edit "+
									data.cnt_error + ' instance(s) of this link.');
									
								display_url = orig_url;
							}
						};
						
						//Shorten the displayed URL if it's > 50 characters
						if ( display_url.length > 50 ){
							display_url = display_url.substr(0, 47) + '...';
						}
						url_el.html(display_url);
					}
				);
                
            } else {
				//It's the same URL, so do nothing.
			}
			edit_button.html('<?php echo js_escape(__('Edit URL', 'broken-link-checker')); ?>');
        }
    });
    
    //Let the user use Enter and Esc as shortcuts for "Save URL" and "Cancel"
    $('input.blc-link-editor').keypress(function (e) {
		if ((e.which && e.which == 13) || (e.keyCode && e.keyCode == 13)) {
			$(this).parents('.blc-row').find('.blc-edit-button').click();
			return false;
		} else if ((e.which && e.which == 27) || (e.keyCode && e.keyCode == 27)) {
			$(this).parents('.blc-row').find('.blc-cancel-button').click();
			return false;
		} else {
			return true;
		}
	});
    
    $(".blc-cancel-button").click(function () { 
		var master = $(this).parents('.blc-row');
		var url_el = $(master).find('.blc-link-url');
		
		//Hide the cancel button
		$(this).parent().hide();
		//Show the un-editable URL again 
		url_el.show();
		//reset and hide the editor
		master.find('.blc-link-editor').hide().val(url_el.attr('href'));
		//Set the edit button to say "Edit URL"
		master.find('.blc-edit-button').html('<?php echo js_escape(__('Edit URL' , 'broken-link-checker')); ?>');
    });
    
    //The unlink button - remove the link/image from all posts, custom fields, etc.
    $(".blc-unlink-button").click(function () { 
    	var me = this;
    	var master = $(me).parents('.blc-row');
		$(me).html('<?php echo js_escape(__('Wait...' , 'broken-link-checker')); ?>');
		
		var link_id = $(me).parents('.blc-row').find('.blc-link-id').html();
        
        $.post(
			"<?php echo admin_url('admin-ajax.php'); ?>",
			{
				'action' : 'blc_unlink',
				'link_id' : link_id
			},
			function (data, textStatus){
				eval('data = ' + data);
				 
				if ( data && ( typeof(data['ok']) != 'undefined') ){
					//Hide the details 
					master.next('.blc-link-details').hide();
					//Flash the main row green to indicate success, then hide it.
					var oldColor = master.css('background-color');
					master.animate({ backgroundColor: "#E0FFB3" }, 200).animate({ backgroundColor: oldColor }, 300, function(){
						master.hide();
					});

					alterLinkCounter(-1);
				} else {
					$(me).html('<?php echo js_escape(__('Unlink' , 'broken-link-checker')); ?>');
					//Show the error message
					alert(data.error);
				}
			}
		);
    });
    
    //The exclude button - Add this link to the exclusion list
    $(".blc-exclude-button").click(function () { 
      	var me = this;
      	var master = $(me).parents('.blc-row');
      	var details = master.next('.blc-link-details');
		$(me).html('<?php echo js_escape(__('Wait...' , 'broken-link-checker')); ?>');
		
		var link_id = $(me).parents('.blc-row').find('.blc-link-id').html();
        
        $.post(
			"<?php echo admin_url('admin-ajax.php'); ?>",
			{
				'action' : 'blc_exclude_link',
				'link_id' : link_id
			},
			function (data, textStatus){
				eval('data = ' + data);
				 
				if ( data && ( typeof(data['ok']) != 'undefined' ) ){
					
					if ( 'broken' == blc_current_filter ){
						//Flash the row green to indicate success, then hide it.
						$(me).replaceWith('<?php echo js_escape(__('Excluded' , 'broken-link-checker')); ?>');
						master.animate({ backgroundColor: "#E0FFB3" }, 200).animate({ backgroundColor: '#E2E2E2' }, 200, function(){
							details.hide();
							master.hide();
							alterLinkCounter(-1);
						});
						master.addClass('blc-excluded-link');
					} else {
						//Flash the row green to indicate success and fade to the "excluded link" color
						master.animate({ backgroundColor: "#E0FFB3" }, 200).animate({ backgroundColor: '#E2E2E2' }, 300);
						master.addClass('blc-excluded-link');
						$(me).replaceWith('<?php echo js_escape(__('Excluded' , 'broken-link-checker')); ?>');
					}
				} else {
					$(me).html('<?php echo js_escape(__('Exclude' , 'broken-link-checker')); ?>');
					alert(data.error);
				}
			}
		);
    });
    
    //--------------------------------------------
    //The search box(es)
    //--------------------------------------------
    
    var searchForm = $('#search-links-dialog');
	    
    searchForm.dialog({
		autoOpen : false,
		dialogClass : 'blc-search-container',
		resizable: false,
	});
    
    $('#blc-open-search-box').click(function(){
    	if ( searchForm.dialog('isOpen') ){
			searchForm.dialog('close');
		} else {
	    	var button_position = $('#blc-open-search-box').offset();
	    	var button_height = $('#blc-open-search-box').outerHeight(true);
	    	var button_width = $('#blc-open-search-box').outerWidth(true);
	    	
			var dialog_width = searchForm.dialog('option', 'width');
						
	    	searchForm.dialog('option', 'position', 
				[ 
					button_position.left - dialog_width + button_width/2, 
					button_position.top + button_height + 1 - $(document).scrollTop()
				]
			);
			searchForm.dialog('open');
		}
	});
	
	$('#blc-cancel-search').click(function(){
		searchForm.dialog('close');
	});
	
	//The "Save This Search Query" button creates a new custom filter based on the current search
	$('#blc-create-filter').click(function(){
		var filter_name = prompt("<?php echo js_escape(__("Enter a name for the new custom filter", 'broken-link-checker')); ?>", "");
		if ( filter_name ){
			$('#blc-custom-filter-name').val(filter_name);
			$('#custom-filter-form').submit();
		}
	});
	
	//Display a confirmation dialog when the user clicks the "Delete This Filter" button 
	$('#blc-delete-filter').click(function(){
		if ( confirm('<?php 
			echo js_escape(  
					__("You are about to delete the current filter.\n'Cancel' to stop, 'OK' to delete", 'broken-link-checker')
				); 
		?>') ){
			return true;
		} else {
			return false;
		}
	});
	
	//--------------------------------------------
    // Bulk actions
    //--------------------------------------------
	
	//Not implemented yet
});

</script>
		<?php
	}
	
	function links_page_css(){
		?>
<style type='text/css'>
.blc-link-editor {
    font-size: 1em;
    width: 95%;
}

.blc-excluded-link {
	background-color: #E2E2E2;
}

.blc-small-image {
	display : block;
	float: left;
	padding-top: 2px;
	margin-right: 3px;
}

.blc-search-container {
	background : white !important;
	border: 3px solid #EEEEEE;
	padding: 12px;
}

.blc-search-container .ui-dialog-titlebar {
	display: none;
	margin: 0px;
}

#search-links-dialog {
	display: none;
}

#search-links-dialog label, #search-links-dialog input.text, #search-links-dialog select { display:block; }
#search-links-dialog input.text { margin-bottom:12px; width:95%; padding: .4em; }
#search-links-dialog select { margin-bottom:12px; padding: .4em; }
#search-links-dialog fieldset { padding:0; border:0; margin-top:25px; }

#blc-search-button-row {
	text-align: center;
}

#blc-search-button-row input {
	padding: 0.4em;
	margin-left: 8px;
	margin-right: 8px;
	margin-top: 8px; 
}

.blc-inline-form {
	display: inline;
}

div.search-box{
	float: right;
	margin-top: -5px;
	margin-right: 0pt;
	margin-bottom: 0pt;
	margin-left: 0pt;
}
</style>
		<?php
	}
	
	function link_details_row($link){
		?>
		<span id='post_date_full' style='display:none;'><?php
			 
    		print $link['post_date'];
    		
    	?></span>
    	<span id='check_date_full' style='display:none;'><?php
    		print $link['last_check'];
    	?></span>
    	<ol style='list-style-type: none; width: 50%; float: right;'>
    		<li><strong><?php _e('Log', 'broken-link-checker'); ?> :</strong>
    	<span class='blc_log'><?php 
    		print nl2br($link['log']); 
    	?></span></li>
		</ol>
		
    	<ol style='list-style-type: none; padding-left: 2px;'>
    	<?php if ( !empty($link['post_date']) ) { ?>
    	<li><strong><?php _e('Post published on', 'broken-link-checker'); ?> :</strong>
    	<span class='post_date'><?php
			echo date_i18n(get_option('date_format'),strtotime($link['post_date']));
    	?></span></li>
    	<?php } ?>
    	<li><strong><?php _e('Link last checked', 'broken-link-checker'); ?> :</strong>
    	<span class='check_date'><?php
			$last_check = strtotime($link['last_check']);
    		if ( $last_check < strtotime('-10 years') ){
				_e('Never', 'broken-link-checker');
			} else {
    			echo date_i18n(get_option('date_format'), $last_check);
    		}
    	?></span></li>
    	
    	<li><strong><?php _e('HTTP code', 'broken-link-checker'); ?> :</strong>
    	<span class='http_code'><?php 
    		print $link['http_code']; 
    	?></span></li>
    	
    	<li><strong><?php _e('Response time', 'broken-link-checker'); ?> :</strong>
    	<span class='request_duration'><?php 
    		printf( __('%2.3f seconds', 'broken-link-checker'), $link['request_duration']); 
    	?></span></li>
    	
    	<li><strong><?php _e('Final URL', 'broken-link-checker'); ?> :</strong>
    	<span class='final_url'><?php 
    		print $link['final_url']; 
    	?></span></li>
    	
    	<li><strong><?php _e('Redirect count', 'broken-link-checker'); ?> :</strong>
    	<span class='redirect_count'><?php 
    		print $link['redirect_count']; 
    	?></span></li>
    	
    	<li><strong><?php _e('Instance count', 'broken-link-checker'); ?> :</strong>
    	<span class='instance_count'><?php 
    		print $link['instance_count']; 
    	?></span></li>
    	
    	<?php if ( intval( $link['check_count'] ) > 0 ){ ?>
    	<li><br/>
		<?php 
			printf(
				_n('This link has failed %d time.', 'This link has failed %d times.', $link['check_count'], 'broken-link-checker'),
				$link['check_count']
			);
		?>
		</li>
    	<?php } ?>
		</ol>
		<?php
	}
    
  /**
   * ws_broken_link_checker::cleanup_links()
   * Remove orphaned links that have no corresponding instances
   *
   * @param int|array $link_id (optional) Only check these links
   * @return bool
   */
    function cleanup_links( $link_id = null ){
		global $wpdb;
		
		$q = "DELETE FROM {$wpdb->prefix}blc_links 
				USING {$wpdb->prefix}blc_links LEFT JOIN {$wpdb->prefix}blc_instances 
					ON {$wpdb->prefix}blc_instances.link_id = {$wpdb->prefix}blc_links.link_id
				WHERE
					{$wpdb->prefix}blc_instances.link_id IS NULL";
					
		if ( $link_id !== null ) {
			if ( !is_array($link_id) ){
				$link_id = array( intval($link_id) );
			}
			$q .= " AND {$wpdb->prefix}blc_links.link_id IN (" . implode(', ', $link_id) . ')';
		}
		
		return $wpdb->query( $q );
	}
	
  /**
   * ws_broken_link_checker::cleanup_instances()
   * Remove instances that reference invalid posts or bookmarks
   *
   * @return bool
   */
	function cleanup_instances(){
		global $wpdb;
		
		//Delete all instances that reference non-existent posts
		$q = "DELETE FROM {$wpdb->prefix}blc_instances 
			  USING {$wpdb->prefix}blc_instances LEFT JOIN {$wpdb->posts} ON {$wpdb->prefix}blc_instances.source_id = {$wpdb->posts}.ID
			  WHERE
			    {$wpdb->posts}.ID IS NULL
				AND ( ( {$wpdb->prefix}blc_instances.source_type = 'post' ) OR ( {$wpdb->prefix}blc_instances.source_type = 'custom_field' ) )";
		$rez = $wpdb->query($q);
		
		//Delete all instances that reference non-existent bookmarks
		$q = "DELETE FROM {$wpdb->prefix}blc_instances 
			  USING {$wpdb->prefix}blc_instances LEFT JOIN {$wpdb->links} ON {$wpdb->prefix}blc_instances.source_id = {$wpdb->links}.link_id
			  WHERE
			    {$wpdb->links}.link_id IS NULL
				AND {$wpdb->prefix}blc_instances.source_type = 'blogroll' ";
		$rez2 = $wpdb->query($q);
		
		return $rez and $rez2;
	}
	
  /**
   * ws_broken_link_checker::parse_post()
   * Parse a post for links and save them to the DB. 
   *
   * @param string $content Post content
   * @param int $post_id Post ID
   * @return void
   */
	function parse_post($content, $post_id){
		//remove all <code></code> blocks first
		$content = preg_replace('/<code[^>]*>.+?<\/code>/si', ' ', $content);
		//Get the post permalink - it's used to resolve relative URLs
		$permalink = get_permalink( $post_id );
		
		//Find links
		if(preg_match_all(blcUtility::link_pattern(), $content, $matches, PREG_SET_ORDER)){
			foreach($matches as $link){
				$url = $link[3];
				$text = strip_tags( $link[5] );
				//FB::log($url, "Found link");
				
				$url = blcUtility::normalize_url($url, $permalink);
				//Skip invalid links
				if ( !$url || (strlen($url)<6) ) continue; 
			    
			    //Create or load the link
			    $link_obj = new blcLink($url);
			    //Add & save a new instance
				$link_obj->add_instance($post_id, 'post', $text, 'link');
			}
		};
		
		//Find images (<img src=...>)
		if(preg_match_all(blcUtility::img_pattern(), $content, $matches, PREG_SET_ORDER)){
			foreach($matches as $img){
				$url = $img[3];
				//FB::log($url, "Found image");
				
				$url = blcUtility::normalize_url($url, $permalink);
				if ( !$url || (strlen($url)<6) ) continue; //skip invalid URLs
				
		        //Create or load the link
			    $link = new blcLink($url);
			    //Add & save a new image instance
				$link->add_instance($post_id, 'post', '', 'image');
			}
		};
	}
	
  /**
   * ws_broken_link_checker::parse_post_meta()
   * Parse a post's custom fields for links and save them in the DB.
   *
   * @param id $post_id
   * @return void
   */
	function parse_post_meta($post_id){
		//Get all custom fields of this post 
		$custom_fields = get_post_custom( $post_id );
		//FB::log($custom_fields, "Custom fields loaded");
		
		//Parse the enabled fields
		foreach( $this->conf->options['custom_fields'] as $field ){
			if ( !isset($custom_fields[$field]) ) continue;
			
			//FB::log($field, "Parsing field");
			
			$values = $custom_fields[$field];
			if ( !is_array( $values ) ) $values = array($values);
			
			foreach( $values as $value ){
				
				//If this is a multiline field take the first line (workaround for the enclosure field). 
				$value = trim( array_shift( explode("\n", $value) ) );

				//Attempt to parse the $value as URL
				$url = blcUtility::normalize_url($value);
				if ( empty($url) ){
					//FB::warn($value, "Invalid URL in custom field ".$field);
					continue;
				}
				
				//FB::log($url, "Found URL");
				$link = new blcLink( $url );
				//FB::log($link, 'Created/loaded link');
				$inst = $link->add_instance( $post_id, 'custom_field', $field, 'link' );
				//FB::log($inst, 'Created instance');				
			} 
		}
		
	}
	
	function parse_blogroll_link( $the_link ){
		//FB::log($the_link, "Parsing blogroll link");
		
		//Attempt to parse the URL
		$url = blcUtility::normalize_url( $the_link['link_url'] );
		if ( empty($url) ){
			//FB::warn( $the_link['link_url'], "Invalid URL in for a blogroll link".$the_link['link_name'] );
			return false;
		}
		
		//FB::log($url, "Found URL");
		$link = new blcLink( $url );
		return $link->add_instance( $the_link['link_id'], 'blogroll', $the_link['link_name'], 'link' );
	}
	
	function start_timer(){
		$this->execution_start_time = microtime_float();
	}
	
	function execution_time(){
		return microtime_float() - $this->execution_start_time;
	}
	
  /**
   * ws_broken_link_checker::work()
   * The main worker function that does all kinds of things.
   *
   * @return void
   */
	function work(){
		global $wpdb;
		
		if ( !$this->acquire_lock() ){
			//FB::warn("Another instance of BLC is already working. Stop.");
			return false;
		}
		
		$this->start_timer();
		
		$max_execution_time = $this->conf->options['max_execution_time'];
	
		/*****************************************
						Preparation
		******************************************/
		// Check for safe mode
		if( blcUtility::is_safe_mode() ){
		    // Do it the safe mode way - obey the existing max_execution_time setting
		    $t = ini_get('max_execution_time');
		    if ($t && ($t < $max_execution_time)) 
		    	$max_execution_time = $t-1;
		} else {
		    // Do it the regular way
		    @set_time_limit( $max_execution_time * 2 ); //x2 should be plenty, running any longer would mean a glitch.
		}
		
		//Don't stop the script when the connection is closed
		ignore_user_abort( true );
		
		//Close the connection as per http://www.php.net/manual/en/features.connection-handling.php#71172
		//This reduces resource usage and may solve the mysterious slowdowns certain users have 
		//encountered when activating the plugin.
		//(Comment out when debugging or you won't get the FirePHP output)
		if ( !defined('BLC_DEBUG') ){
			ob_end_clean();
	 		header("Connection: close");
			ob_start();
			echo ('Connection closed'); //This could be anything
			$size = ob_get_length();
			header("Content-Length: $size");
	 		ob_end_flush(); // Strange behaviour, will not work
	 		flush();        // Unless both are called !
 		}
 		
		$check_threshold = date('Y-m-d H:i:s', strtotime('-'.$this->conf->options['check_threshold'].' hours'));
		$recheck_threshold = date('Y-m-d H:i:s', strtotime('-20 minutes'));
		
		$orphans_possible = false;
		
		$still_need_resynch = $this->conf->options['need_resynch'];
		
		/*****************************************
				Parse posts and bookmarks
		******************************************/
		
		if ( $this->conf->options['need_resynch'] ) {
			
			//FB::log("Looking for posts and bookmarks that need parsing...");
			
			$tsynch = $wpdb->prefix.'blc_synch';
			$tposts = $wpdb->posts;
			$tlinks = $wpdb->links;
			
			$synch_q = "SELECT $tsynch.source_id, $tsynch.source_type, $tposts.post_content, $tlinks.link_url, $tlinks.link_id, $tlinks.link_name
	
				FROM 
				 $tsynch LEFT JOIN $tposts 
				   ON ($tposts.id = $tsynch.source_id AND $tsynch.source_type='post')
				 LEFT JOIN $tlinks 
				   ON ($tlinks.link_id = $tsynch.source_id AND $tsynch.source_type='blogroll')
				
				WHERE 
				  $tsynch.synched = 0
				  
				LIMIT 50";
				  
			while ( $rows = $wpdb->get_results($synch_q, ARRAY_A) ) {
				
				//FB::log("Found ".count($rows)." items to analyze.");
				
				foreach ($rows as $row) {
					
					if ( $row['source_type'] == 'post' ){
						
						//FB::log("Parsing post ".$row['source_id']);
						
						//Remove instances associated with this post
						$q = "DELETE FROM {$wpdb->prefix}blc_instances 
							  WHERE source_id = %d AND (source_type = 'post' OR source_type='custom_field')";
						$q = $wpdb->prepare($q, intval($row['source_id']));
						
						//FB::log($q, "Executing query");
				        
				        if ( $wpdb->query( $q ) === false ){
							//FB::error($wpdb->last_error, "Database error");
						}
				        
				        //Gather links and images from the post
				        $this->parse_post( $row['post_content'], $row['source_id'] );
				        //Gather links from custom fields
				        $this->parse_post_meta( $row['source_id'] );
				        
						//Some link records might be orhpaned now 
						$orphans_possible = true;
						
					} else {
						
						//FB::log("Parsing bookmark ".$row['source_id']);
						
						//Remove instances associated with this bookmark
						$q = "DELETE FROM {$wpdb->prefix}blc_instances 
							  WHERE source_id = %d AND source_type = 'blogroll'";
						$q = $wpdb->prepare($q, intval($row['source_id']));
						//FB::log($q, "Executing query");
						
				        if ( $wpdb->query( $q ) === false ){
							//FB::error($wpdb->last_error, "Database error");
						}
						
						//(Re)add the instance and link
						$this->parse_blogroll_link( $row );
						
						//Some link records might be orhpaned now 
						$orphans_possible = true;
						
					}
					
					//Update the table to indicate the item has been parsed
				    $this->mark_synched( $row['source_id'], $row['source_type'] );
				    
				    //Check if we still have some execution time left
					if( $this->execution_time() > $max_execution_time ){
						//FB::log('The alloted execution time has run out');
						$this->cleanup_links();
						$this->release_lock();
						return;
					}
					
				}

			}
			
			//FB::log('No unparsed items found.');
			$still_need_resynch = false;
			
			if ( $wpdb->last_error ){
				//FB::error($wpdb->last_error, "Database error");
			}
			
		} else {
			//FB::log('Resynch not required.');
		}
		
		/******************************************
				    Resynch done?
		*******************************************/
		if ( $this->conf->options['need_resynch'] && !$still_need_resynch ){
			$this->conf->options['need_resynch']  = $still_need_resynch;
			$this->conf->save_options();
		}
		
		/******************************************
				    Remove orphaned links
		*******************************************/
		
		if ( $orphans_possible ) {
			//FB::log('Cleaning up the link table.');
			$this->cleanup_links();
		}
		
		//Check if we still have some execution time left
		if( $this->execution_time() > $max_execution_time ){
			//FB::log('The alloted execution time has run out');
			$this->release_lock();
			return;
		}
		
		/*****************************************
						Check links
		******************************************/
		//FB::log('Looking for links to check (threshold : '.$check_threshold.')...');
		
		//Select some links that haven't been checked for a long time or
		//that are broken and need to be re-checked again.
		
		//Note : This is a slow query, but AFAIK there is no way to speed it up.
		//I could put an index on last_check, but that value is almost certainly unique
		//for each row so it wouldn't be much better than a full table scan.
		$q = "SELECT *, ( last_check < %s ) AS meets_check_threshold
			  FROM {$wpdb->prefix}blc_links
		      WHERE 
			  	( last_check < %s ) 
				OR 
		 	  	( 
					( http_code >= 400 OR http_code < 200 OR timeout = 1) 
					AND check_count < %d 
					AND check_count > 0  
					AND last_check < %s 
				) 
			  ORDER BY last_check ASC
		 	  LIMIT 50";
		$link_q = $wpdb->prepare($q, $check_threshold, $check_threshold, $this->conf->options['recheck_count'], $recheck_threshold);
		//FB::log($link_q);
		
		while ( $links = $wpdb->get_results($link_q, ARRAY_A) ){
		
			//some unchecked links found
			//FB::log("Checking ".count($links)." link(s)");
			
			foreach ($links as $link) {
				$link_obj = new blcLink($link);
				
				//Does this link need to be checked?
        		if ( !$this->is_excluded( $link['url'] ) ) {
        			//Yes, do it
        			//FB::log("Checking link {$link[link_id]}");
					$link_obj->check( $this->conf->options['timeout'] );
					$link_obj->save();
				} else {
					//Nope, mark it as already checked.
					//FB::info("The URL {$link_obj->url} is excluded, marking link {$link_obj->link_id} as already checked.");
					$link_obj->last_check = date('Y-m-d H:i:s');
					$link_obj->http_code = 200; //Use a fake code so that the link doesn't show up in queries looking for broken links.
					$link_obj->timeout = false;
					$link_obj->request_duration = 0; 
					$link_obj->log = __("This link wasn't checked because a matching keyword was found on your exclusion list.", 'broken-link-checker');
					$link_obj->save();
				}
				
				//Check if we still have some execution time left
				if( $this->execution_time() > $max_execution_time ){
					//FB::log('The alloted execution time has run out');
					$this->release_lock();
					return;
				}
			}
		}
		//FB::log('No links need to be checked right now.');
		
		$this->release_lock();
		//FB::log('All done.');
	}
	
	function ajax_full_status( ){
		$status = $this->get_status();
		$text = $this->status_text( $status );
		
		echo json_encode( array(
			'text' => $text,
			'status' => $status, 
		 ) );
		
		die();
	}
	
  /**
   * ws_broken_link_checker::status_text()
   * Generates a status message based on the status info in $status
   *
   * @param array $status
   * @return string
   */
	function status_text( $status ){
		$text = '';
	
		if( $status['broken_links'] > 0 ){
			$text .= sprintf( 
				"<a href='%s' title='" . __('View broken links', 'broken-link-checker') . "'><strong>". 
					_n('Found %d broken link', 'Found %d broken links', $status['broken_links'], 'broken-link-checker') .
				"</strong></a>",
			  	admin_url('tools.php?page=view-broken-links'), 
				$status['broken_links']
			);
		} else {
			$text .= __("No broken links found.", 'broken-link-checker');
		}
		
		$text .= "<br/>";
		
		if( $status['unchecked_links'] > 0) {
			$text .= sprintf( 
				_n('%d URL in the work queue', '%d URLs in the work queue', $status['unchecked_links'], 'broken-link-checker'), 
				$status['unchecked_links'] );
		} else {
			$text .= __("No URLs in the work queue.", 'broken-link-checker');
		}
		
		$text .= "<br/>";
		if ( $status['known_links'] > 0 ){
			$text .= sprintf( 
				_n('Detected %d unique URL', 'Detected %d unique URLs', $status['known_links'], 'broken-link-checker') .
					' ' . _n('in %d link', 'in %d links', $status['known_instances'], 'broken-link-checker'),
				$status['known_links'],
				$status['known_instances']
			 );
			if ($this->conf->options['need_resynch']){
				$text .= ' ' . __('and still searching...', 'broken-link-checker');
			} else {
				$text .= '.';
			}
		} else {
			if ($this->conf->options['need_resynch']){
				$text .= __('Searching your blog for links...', 'broken-link-checker');
			} else {
				$text .= __('No links detected.', 'broken-link-checker');
			}
		}
		
		return $text;
	}
	
	function ajax_dashboard_status(){
		//Just display the full status.
		$this->ajax_full_status( );
	}
	
  /**
   * ws_broken_link_checker::get_status()
   * Returns an array with various status information about the plugin. Array key reference: 
   *	check_threshold 	- date/time; links checked before this threshold should be checked again.
   *	recheck_threshold 	- date/time; broken links checked before this threshold should be re-checked.
   *	known_links 		- the number of detected unique URLs (a misleading name, yes).
   *	known_instances 	- the number of detected link instances, i.e. actual link elements in posts and other places.
   *	broken_links		- the number of detected broken links.	
   *	unchecked_links		- the number of URLs that need to be checked ASAP; based on check_threshold and recheck_threshold.
   *
   * @return array
   */
	function get_status(){
		global $wpdb;
		
		$check_threshold=date('Y-m-d H:i:s', strtotime('-'.$this->conf->options['check_threshold'].' hours'));
		$recheck_threshold=date('Y-m-d H:i:s', strtotime('-20 minutes'));
		
		$q = "SELECT count(*) FROM {$wpdb->prefix}blc_links WHERE 1";
		$known_links = $wpdb->get_var($q);
		
		$q = "SELECT count(*) FROM {$wpdb->prefix}blc_instances WHERE 1";
		$known_instances = $wpdb->get_var($q);
		
		/*
		$q = "SELECT count(*) FROM {$wpdb->prefix}blc_links 
			  WHERE check_count > 0 AND ( http_code < 200 OR http_code >= 400 OR timeout = 1 ) AND ( http_code <> ".BLC_CHECKING." )";
		$broken_links = $wpdb->get_var($q);
		*/
		$broken_links = $this->get_links( $this->native_filters['broken'], 0, 0, true );
		
		$q = "SELECT count(*) FROM {$wpdb->prefix}blc_links
		      WHERE 
			  	( ( last_check < '$check_threshold' ) OR 
		 	  	  ( 
					 ( http_code >= 400 OR http_code < 200 ) 
					 AND check_count < 3 
					 AND last_check < '$recheck_threshold' ) 
				  )";
		$unchecked_links = $wpdb->get_var($q);
		
		return array(
			'check_threshold' => $check_threshold,
			'recheck_threshold' => $recheck_threshold,
			'known_links' => $known_links,
			'known_instances' => $known_instances,
			'broken_links' => $broken_links,
			'unchecked_links' => $unchecked_links,
		 );
	}
	
	function ajax_work(){
		//Run the worker function 
		$this->work();
		die();
	}
	
	function ajax_discard(){
		//TODO:Rewrite to use JSON instead of plaintext		
		if (!current_user_can('edit_others_posts')){
			die( __("You're not allowed to do that!", 'broken-link-checker') );
		}
		
		if ( isset($_POST['link_id']) ){
			//Load the link
			$link = new blcLink( intval($_POST['link_id']) );
			
			if ( !$link->valid() ){
				printf( __("Oops, I can't find the link %d", 'broken-link-checker'), intval($_POST['link_id']) );
				die();
			}
			//Make it appear "not broken"  
			$link->last_check = date('Y-m-d H:i:s');
			$link->http_code =  200;
			$link->timeout = 0;
			$link->check_count = 0;
			$link->log = __("This link was manually marked as working by the user.", 'broken-link-checker');
			
			//Save the changes
			if ( $link->save() ){
				die( "OK" );
			} else {
				die( __("Oops, couldn't modify the link!", 'broken-link-checker') ) ;
			}
		} else {
			die( __("Error : link_id not specified", 'broken-link-checker') );
		}
	}
	
	function ajax_edit(){
		if (!current_user_can('edit_others_posts')){
			die( json_encode( array(
					'error' => __("You're not allowed to do that!", 'broken-link-checker') 
				 )));
		}
		
		if ( isset($_GET['link_id']) && !empty($_GET['new_url']) ){
			//Load the link
			$link = new blcLink( intval($_GET['link_id']) );
			
			if ( !$link->valid() ){
				die( json_encode( array(
					'error' => sprintf( __("Oops, I can't find the link %d", 'broken-link-checker'), intval($_GET['link_id']) ) 
				 )));
			}
			
			$new_url = blcUtility::normalize_url($_GET['new_url']);
			if ( !$new_url ){
				die( json_encode( array(
					'error' => __("Oops, the new URL is invalid!", 'broken-link-checker') 
				 )));
			}
			
			//Try and edit the link
			$rez = $link->edit($new_url);
			
			if ( $rez == false ){
				die( json_encode( array(
					'error' => __("An unexpected error occured!", 'broken-link-checker')
				 )));
			} else {
				$rez['ok'] = __('OK', 'broken-link-checker');
				die( json_encode($rez) );
			}
			
		} else {
			die( json_encode( array(
					'error' => __("Error : link_id or new_url not specified", 'broken-link-checker')
				 )));
		}
	}
	
	function ajax_unlink(){
		if (!current_user_can('edit_others_posts')){
			die( json_encode( array(
					'error' => __("You're not allowed to do that!", 'broken-link-checker') 
				 )));
		}
		
		if ( isset($_POST['link_id']) ){
			//Load the link
			$link = new blcLink( intval($_POST['link_id']) );
			
			if ( !$link->valid() ){
				die( json_encode( array(
					'error' => sprintf( __("Oops, I can't find the link %d", 'broken-link-checker'), intval($_POST['link_id']) ) 
				 )));
			}
			
			//Try and unlink it
			if ( $link->unlink() ){
				die( json_encode( array(
					'ok' => sprintf( __("URL %s was removed.", 'broken-link-checker'), $link->url ) 
				 )));
			} else {
				die( json_encode( array(
					'error' => __("The plugin failed to remove the link.", 'broken-link-checker') 
				 )));
			}
			
		} else {
			die( json_encode( array(
					'error' => __("Error : link_id not specified", 'broken-link-checker') 
				 )));
		}
	}
	
	function ajax_link_details(){
		global $wpdb;
		
		if (!current_user_can('edit_others_posts')){
			die( __("You don't have sufficient privileges to access this information!", 'broken-link-checker') );
		}
		
		//FB::log("Loading link details via AJAX");
		
		if ( isset($_GET['link_id']) ){
			//FB::info("Link ID found in GET");
			$link_id = intval($_GET['link_id']);
		} else if ( isset($_POST['link_id']) ){
			//FB::info("Link ID found in POST");
			$link_id = intval($_POST['link_id']);
		} else {
			//FB::error('Link ID not specified, you hacking bastard.');
			die( __('Error : link ID not specified', 'broken-link-checker') );
		}
		
		//Load the link. link_details_row needs it as an array, so 
		//we'll have to do this the long way.
		$q = "SELECT 
				 links.*, 
				 COUNT(*) as instance_count
				
			  FROM 
				 {$wpdb->prefix}blc_links AS links, 
				 {$wpdb->prefix}blc_instances as instances
				
			   WHERE
				 links.link_id = %d
				
			   GROUP BY links.link_id";
		
		$link = $wpdb->get_row( $wpdb->prepare($q, $link_id), ARRAY_A );
		if ( is_array($link) ){
			//FB::info($link, 'Link loaded');
			$this->link_details_row($link);
			die();
		} else {
			printf( __('Failed to load link details (%s)', 'broken-link-checker'), $wpdb->last_error );
			die ();
		}
	}
	
	function ajax_exclude_link(){
		if ( !current_user_can('manage_options') ){
			die( json_encode( array(
					'error' => __("You're not allowed to do that!", 'broken-link-checker') 
				 )));
		}
		
		if ( isset($_POST['link_id']) ){
			//Load the link
			$link = new blcLink( intval($_POST['link_id']) );
			
			if ( !$link->valid() ){
				die( json_encode( array(
					'error' => sprintf( __("Oops, I can't find the link %d", 'broken-link-checker'), intval($_POST['link_id']) ) 
				 )));
			}
			
			//Add the URL to the exclusion list
			if ( !in_array( $link->url, $this->conf->options['exclusion_list'] ) ){
				$this->conf->options['exclusion_list'][] = $link->url;
				//Also mark it as already checked so that it doesn't show up with other broken links.
				//FB::info("The URL {$link->url} is excluded, marking link {$link->link_id} as already checked.");
				$link->last_check = date('Y-m-d H:i:s');
				$link->http_code = 200; //Use a fake code so that the link doesn't show up in queries looking for broken links.
				$link->timeout = false;
				$link->request_duration = 0; 
				$link->log = __("This link wasn't checked because a matching keyword was found on your exclusion list.", 'broken-link-checker');
				$link->save();
			}
				 
			$this->conf->save_options();
			
			die( json_encode( array(
					'ok' => sprintf( __('URL %s added to the exclusion list', 'broken-link-checker'), $link->url ) 
				 )));
		} else {
			die( json_encode( array(
					'error' => __("Link ID not specified", 'broken-link-checker') 
			 )));
		}
	}
	
  /**
   * ws_broken_link_checker::acquire_lock()
   * Create and lock a temporary file.
   *
   * @return bool
   */
	function acquire_lock(){
		//Maybe we already have the lock?
		if ( $this->lockfile_handle ){
			return true;
		}
		
		$fn = $this->lockfile_name();
		if ( $fn ){
			//Open the lockfile
			$this->lockfile_handle = fopen($fn, 'w+');
			if ( $this->lockfile_handle ){
				//Do an exclusive lock
				if (flock($this->lockfile_handle, LOCK_EX | LOCK_NB)) {
					//File locked successfully 
					return true; 
				} else {
					//Something went wrong
					fclose($this->lockfile_handle);
					$this->lockfile_handle = null;
				    return false;
				}
			} else {
				//Can't open the file, fail.
				return false;
			}
		} else {
			//Uh oh, can't generate a lockfile name. This is bad.
			//FB::error("Can't find a writable directory to use for my lock file!"); 
			return false;
		};
	}
	
  /**
   * ws_broken_link_checker::release_lock()
   * Unlock and delete the temporary file
   *
   * @return bool
   */
	function release_lock(){
		if ( $this->lockfile_handle ){
			//Close the file (implicitly releasing the lock)
			fclose( $this->lockfile_handle );
			//Delete the file
			$fn = $this->lockfile_name();
			if ( file_exists( $fn ) ) {
				unlink( $fn );
			}
			$this->lockfile_handle = null;			
			return true;
		} else {
			//We didn't have the lock anyway...
			return false;
		}
	}
	
  /**
   * ws_broken_link_checker::lockfile_name()
   * Generate system-specific lockfile filename
   *
   * @return string A filename or FALSE on error 
   */
	function lockfile_name(){
		//Try the user-specified temp. directory first, if any
		if ( !empty( $this->conf->options['custom_tmp_dir'] ) ) {
			if ( @is_writable($this->conf->options['custom_tmp_dir']) && @is_dir($this->conf->options['custom_tmp_dir']) ) {
				return trailingslashit($this->conf->options['custom_tmp_dir']) . 'wp_blc_lock';
			} else {
				return false;
			}
		}
		
		//Try the plugin's own directory.
		if ( @is_writable( dirname(__FILE__) ) ){
			return dirname(__FILE__) . '/wp_blc_lock';
		} else {
			
			//Try the system-wide temp directory
			$path = sys_get_temp_dir();
			if ( $path && @is_writable($path)){
				return trailingslashit($path) . 'wp_blc_lock';
			}
			
			//Try the upload directory.  
			$path = ini_get('upload_tmp_dir');
			if ( $path && @is_writable($path)){
				return trailingslashit($path) . 'wp_blc_lock';
			}
			
			//Fail
			return false;
		}
	}
	
	function hook_add_link( $link_id ){
		$this->mark_unsynched( $link_id, 'blogroll' );
	}
	
	function hook_edit_link( $link_id ){
		$this->mark_unsynched( $link_id, 'blogroll' );
	}
	
	function hook_delete_link( $link_id ){
		global $wpdb;
		//Delete the synch record
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}blc_synch WHERE source_id = %d AND source_type='blogroll'", $link_id ) );
		
		//Get the matching instance record.
		$inst = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}blc_instances WHERE source_id = %d AND source_type = 'blogroll'", $link_id), ARRAY_A );
		
		if ( !$inst ) {
			//No instance record? No problem.
			return;
		}

		//Remove it
		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}blc_instances WHERE instance_id = %d", $inst['instance_id']) );

		//Remove the link that was associated with this instance if it has no more related instances.
		$this->cleanup_links( $inst['link_id'] );
	}
	
	function hook_wp_dashboard_setup(){
		if ( function_exists( 'wp_add_dashboard_widget' ) ) {
			wp_add_dashboard_widget(
				'blc_dashboard_widget', 
				'Broken Link Checker', 
				array( &$this, 'dashboard_widget' ),
				array( &$this, 'dashboard_widget_control' )
			 );
		}
	}
	
	function lockfile_warning(){
		$my_dir =  '/plugins/' . basename(dirname(__FILE__)) . '/';
		$settings_page = admin_url( 'options-general.php?page=link-checker-settings#lockfile_directory' );
		
		//Make the notice customized to the current settings
		if ( !empty($this->conf->options['custom_tmp_dir']) ){
			$action_notice = sprintf(
				__('The current temporary directory is not accessible; please <a href="%s">set a different one</a>.', 'broken-link-checker'),
				$settings_page
			);
		} else {
			$action_notice = sprintf(
				__('Please make the directory <code>%1$s</code> writable by plugins or <a href="%2$s">set a custom temporary directory</a>.', 'broken-link-checker'),
				$my_dir, $settings_page
			);
		}
					
		echo sprintf('
			<div id="blc-lockfile-warning" class="error"><p>
				<strong>' . __("Broken Link Checker can't create a lockfile.", 'broken-link-checker') . 
				'</strong> %s <a href="javascript:void(0)" onclick="jQuery(\'#blc-lockfile-details\').toggle()">' . 
				__('Details', 'broken-link-checker') . '</a> </p>
				
				<div id="blc-lockfile-details" style="display:none;"><p>' . 
				__("The plugin uses a file-based locking mechanism to ensure that only one instance of the resource-heavy link checking algorithm is running at any given time. Unfortunately, BLC can't find a writable directory where it could store the lockfile - it failed to detect the location of your server's temporary directory, and the plugin's own directory isn't writable by PHP. To fix this problem, please make the plugin's directory writable or enter a specify a custom temporary directory in the plugin's settings.", 'broken-link-checker') .
				'</p> 
				</div>
			</div>',
			$action_notice);
	}
	
  /**
   * wsBrokenLinkChecker::get_debug_info()
   * Collect various debugging information and return it in an associative array
   *
   * @return array
   */
	function get_debug_info(){
		global $wpdb;
		
		//Collect some information that's useful for debugging 
		$debug = array();
		
		//PHP version. Any one is fine as long as WP supports it.
		$debug[ __('PHP version', 'broken-link-checker') ] = array(
			'state' => 'ok',
			'value' => phpversion(), 
		);
		
		//MySQL version
		$debug[ __('MySQL version', 'broken-link-checker') ] = array(
			'state' => 'ok',
			'value' => @mysql_get_server_info( $wpdb->dbh ), 
		);
		
		//CURL presence and version
		if ( function_exists('curl_version') ){
			$version = curl_version();
			
			if ( version_compare( $version['version'], '7.16.0', '<=' ) ){
				$data = array(
					'state' => 'warning', 
					'value' => $version['version'],
					'message' => __('You have an old version of CURL. Redirect detection may not work properly.', 'broken-link-checker'),
				);
			} else {
				$data = array(
					'state' => 'ok', 
					'value' => $version['version'],
				);
			}
			
		} else {
			$data = array(
				'state' => 'warning', 
				'value' => __('Not installed', 'broken-link-checker'),
			);
		}
		$debug[ __('CURL version', 'broken-link-checker') ] = $data;
		
		//Snoopy presence
		if ( class_exists('Snoopy') ){
			$data = array(
				'state' => 'ok',
				'value' => __('Installed', 'broken-link-checker'),
			);
		} else {
			//No Snoopy? This should never happen, but if it does we *must* have CURL. 
			if ( function_exists('curl_init') ){
				$data = array(
					'state' => 'ok',
					'value' => __('Not installed', 'broken-link-checker'),
				);
			} else {
				$data = array(
					'state' => 'error',
					'value' => __('Not installed', 'broken-link-checker'),
					'message' => __('You must have either CURL or Snoopy installed for the plugin to work!', 'broken-link-checker'),
				);
			}
			
		}
		$debug['Snoopy'] = $data;
		
		//Safe_mode status
		if ( blcUtility::is_safe_mode() ){
			$debug['Safe mode'] = array(
				'state' => 'warning',
				'value' => __('On', 'broken-link-checker'),
				'message' => __('Redirects may be detected as broken links when safe_mode is on.', 'broken-link-checker'),
			);
		} else {
			$debug['Safe mode'] = array(
				'state' => 'ok',
				'value' => __('Off', 'broken-link-checker'),
			);
		}
		
		//Open_basedir status
		if ( blcUtility::is_open_basedir() ){
			$debug['open_basedir'] = array(
				'state' => 'warning',
				'value' => sprintf( __('On ( %s )', 'broken-link-checker'), ini_get('open_basedir') ),
				'message' => __('Redirects may be detected as broken links when open_basedir is on.', 'broken-link-checker'),
			);
		} else {
			$debug['open_basedir'] = array(
				'state' => 'ok',
				'value' => __('Off', 'broken-link-checker'),
			);
		}
		
		//Lockfile location
		$lockfile = $this->lockfile_name();
		if ( $lockfile ){
			$debug['Lockfile'] = array(
				'state' => 'ok',
				'value' => $lockfile,
			);
		} else {
			$debug['Lockfile'] = array(
				'state' => 'error',
				'message' => __("Can't create a lockfile. Please specify a custom temporary directory.", 'broken-link-checker'),
			);
		}
		
		return $debug;
	}
	
  /**
   * wsBrokenLinkChecker::load_language()
   * Load the plugin's textdomain
   *
   * @return void
   */
	function load_language(){
		load_plugin_textdomain( 'broken-link-checker', false, basename(dirname($this->loader)) . '/languages' );
	}
	
  /**
   * wsBrokenLinkChecker::get_links()
   * Get the list of links that match a given filter. 
   *
   * @param array|null $filter The filter to apply. Set this to null to return all links (default).
   * @param integer $offset	Skip this many links from the beginning. If this parameter is nonzero you must also set the next one.
   * @param integer $max_results The maximum number of links to return.
   * @param bool $count_only Only return the total number of matching links, not the links themselves
   * @return array|int Either an array of links, or the number of matching links. Null on error.
   */
	function get_links( $filter = null, $offset = 0, $max_results = 0, $count_only = false){
		global $wpdb; 
		
		//Figure out the WHERE expression for this filter
		$where_expr = '1'; //default = select all links
		
		if ( !empty($filter) ){
			
			//Is this a custom search filter?
			if ( empty($filter['is_search']) ){
				//It's a native filter, so it should have the WHERE epression already set
				$where_expr = $filter['where_expr'];
			} else {
				//It's a search filter, so we must build the WHERE expr for the specific query
				//from the query parameters.
				
				$params = $this->get_search_params($filter);
				
				//Generate the individual clauses of the WHERE expression
				$pieces = array();				
				
				//Anchor text - use fulltext search
				if ( !empty($params['s_link_text']) ){
					$pieces[] = 'MATCH(instances.link_text) AGAINST("' . $wpdb->escape($params['s_link_text']) . '")';
				}
				
				//URL - try to match both the initial URL and the final URL.
				//There is limited wildcard support, e.g. "google.*/search" will match both 
				//"google.com/search" and "google.lv/search"  
				if ( !empty($params['s_link_url']) ){
					$s_link_url = like_escape($wpdb->escape($params['s_link_url']));
					$s_link_url = str_replace('*', '%', $s_link_url);
					
					$pieces[] = '(links.url LIKE "%'. $s_link_url .'%") OR '.
						        '(links.final_url LIKE "%'. $s_link_url .'%")';
				}
				
				//Link type should match either the instance_type or the source_type
				if ( !empty($params['s_link_type']) ){
					$s_link_type = $wpdb->escape($params['s_link_type']);
					$pieces[] = "instances.instance_type = '$s_link_type' OR instances.source_type='$s_link_type'";
				}
				
				//HTTP code - the user can provide a list of HTTP response codes and code ranges.
				//Example : 201,400-410,500 
				if ( !empty($params['s_http_code']) ){
					//Strip spaces.
					$params['s_http_code'] = str_replace(' ', '', $params['s_http_code']);
					//Split by comma
					$codes = explode(',', $params['s_http_code']);
					
					$individual_codes = array();
					$ranges = array();
					
					//Try to parse each response code or range. Invalid ones are simply ignored.
					foreach($codes as $code){
						if ( is_numeric($code) ){
							//It's a single number
							$individual_codes[] = abs(intval($code));
						} elseif ( strpos($code, '-') !== false ) {
							//Try to parse it as a range
							$range = explode( '-', $code, 2 );
							if ( (count($range) == 2) && is_numeric($range[0]) && is_numeric($range[0]) ){
								//Make sure the smaller code comes first
								$range = array( intval($range[0]), intval($range[1]) );
								$ranges[] = array( min($range), max($range) );
							}
						}
					}
					
					$piece = array();
					
					//All individual response codes get one "http_code IN (...)" clause 
					if ( !empty($individual_codes) ){
						$piece[] = '(links.http_code IN ('. implode(', ', $individual_codes) .'))';
					}
					
					//Ranges get a "http_code BETWEEN min AND max" clause each
					if ( !empty($ranges) ){
						$range_strings = array();
						foreach($ranges as $range){
							$range_strings[] = "(links.http_code BETWEEN $range[0] AND $range[1])";
						}
						$piece[] = '( ' . implode(' OR ', $range_strings) . ' )';
					}
					
					//Finally, generate a composite WHERE clause for both types of response code queries
					if ( !empty($piece) ){
						$pieces[] = implode(' OR ', $piece);
					}
					
				}			
				
				//Custom filters can optionally call one of the native filters
				//to narrow down the result set.
				if ( !empty($params['s_filter']) && isset($this->native_filters[$params['s_filter']]) ){
					$pieces[] = $this->native_filters[$params['s_filter']]['where_expr'];
				}
				
				if ( !empty($pieces) ){
					$where_expr = "\t( " . implode(" ) AND\n\t( ", $pieces) . ' ) ';
				}
			}
					
		}
		
		if ( $count_only ){
			//Only get the number of matching links. This lets us use a simplified query with less joins.
			$q = "
				SELECT COUNT(*)
				FROM (	
					SELECT 0
					
					FROM 
						{$wpdb->prefix}blc_links AS links, 
						{$wpdb->prefix}blc_instances as instances
					
					WHERE
						links.link_id = instances.link_id
						AND ". $where_expr ."
					
				   GROUP BY links.link_id) AS foo";
			return $wpdb->get_var($q);
		} else {
			//Select the required links + 1 instance per link + 1 post for instances contained in posts.
			$q = "SELECT 
					 links.*, 
					 instances.instance_id, instances.source_id, instances.source_type, 
					 instances.link_text, instances.instance_type,
					 COUNT(*) as instance_count,
					 posts.post_title,
					 posts.post_date
					
				  FROM 
					 {$wpdb->prefix}blc_links AS links, 
					 {$wpdb->prefix}blc_instances as instances LEFT JOIN {$wpdb->posts} as posts ON instances.source_id = posts.ID
					
				   WHERE
					 links.link_id = instances.link_id
					 AND ". $where_expr ."
					
				   GROUP BY links.link_id";
			if ( $max_results || $offset ){
				$q .= "\nLIMIT $offset, $max_results";
			}
			
			return $wpdb->get_results($q, ARRAY_A);
		}
	}

}//class ends here

} // if class_exists...

?>