<?php /* Startup Conference Plovdiv 2010 */ ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head>
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
	<title><?php wp_title('&laquo;', true, 'right'); ?> <?php bloginfo('name'); ?></title>

  <link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />
  <link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
  <link rel="shortcut icon" href="<?php bloginfo('template_directory'); ?>/favicon.png" />

	<?php wp_head(); ?>
</head>

<body id="body_bg" <?php body_class(); ?>>
  <!-- Facebook connect -->
  <script src="http://static.ak.connect.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php/bg_BG" type="text/javascript"></script>
  <script type="text/javascript">FB.init("144701d032d3ea99a02b3b3d39d505b0");</script>

  <!-- header -->
	<div id="header">
		<div class="header_in">
		  <a href="<?php bloginfo('url'); ?>/" class="logo" title="<?php bloginfo('name'); ?>"></a>
			<h1 title="<?php bloginfo('name'); ?>"><?php bloginfo('description'); ?></h1>
		</div>
	</div>
	<!-- /header -->
	
	<!-- navigation -->
	<div id="main_menu">
		<ul>
		  <li<?php if(is_home()):?> class="current_page_item"<?php endif; ?>><a href="<?php echo get_settings('home'); ?>" title="Начало"><span>Начало</span></a></li>
      <?php
        echo preg_replace('@\<li([^>]*)>\<a([^>]*)>(.*?)\<\/a>@i', '<li$1><a$2><span>$3</span></a>', wp_list_pages('echo=0&depth=1&orderby=name&title_li='));
      ?>
          </ul>
	</div>
	<!-- /navigation -->

  <?php 
    if(is_home()):
  ?>
    <!-- intro -->
	  <div id="intro">
		  <div class="intro_in">
<!--?php echo do_shortcode('[slideshow id="1" w="940" h="200"]'); ?-->
<?php echo do_shortcode('[nggallery id=1 template=galleryview images=all]'); ?>
		  </div>
	  </div>
	  <!-- /intro -->
	<?php
	  endif;
	?>
	
	<div class="wrapper_bg">
		<div class="wrapper">
<!-- end header -->

