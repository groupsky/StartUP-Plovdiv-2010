<!-- begin sidebar -->
<div id="right_col">
  <?php if (!function_exists('dynamic_sidebar') || !dynamic_sidebar('sidebar-1')): ?>
	<div class="social">
		<a href="http://twitter.com/startupbg" target="_blank"><img src="<?php bloginfo('template_directory'); ?>/img/twitter.png" alt="Startup Conference on Twitter" title="Startup Conference on Twitter" /></a>
		<a href="http://facebook.com/startupbg" target="_blank"><img src="<?php bloginfo('template_directory'); ?>/img/facebook.png" alt="Startup Conference on FaceBook" title="Startup Conference on FaceBook" /></a>
		<a href="http://picasaweb.google.com/startupbg" target="_blank"><img src="<?php bloginfo('template_directory'); ?>/img/picasa.png" alt="Startup Conference pictures in Picasa" title="Startup Conference pictures in Picasa" /></a>
		<a href="<?php bloginfo('rss2_url'); ?>" class="last"><img src="<?php bloginfo('template_directory'); ?>/img/rss.png" alt="Subscribe to Startup Conference RSS feeds" title="Subscribe to Startup Conference RSS feeds" /></a>
		<div class="clr"></div>
	</div>
	<div class="flexipages_widget">
		<h3 class="tickets"><?php echo preg_replace('@\<li([^>]*)>\<a([^>]*)>(.*?)\<\/a>\<\/li>@i', '<a$2>', wp_list_pages('include=2&title_li=&echo=0')); ?><span>Билети</span></a></h3>
		<h3 class="live"><a href="#" title="Гледай на живо"><span>На живо</span></a></h3>
	</div>
	<div class="gallery">
		<h3>Снимки от Startup 2009</h3>
		<a href="#" class="thumb"><img src="<?php bloginfo('template_directory'); ?>/img/thumb_1.jpg" alt="pictures from the conference" title="pictures from the conference" /></a>
		<a href="#" class="thumb_last"><img src="<?php bloginfo('template_directory'); ?>/img/thumb_2.jpg" alt="pictures from the conference" title="pictures from the conference" /></a>
		<a href="#" class="thumb"><img src="<?php bloginfo('template_directory'); ?>/img/thumb_2.jpg" alt="pictures from the conference" title="pictures from the conference" /></a>
		<a href="#" class="thumb_last"><img src="<?php bloginfo('template_directory'); ?>/img/thumb_1.jpg" alt="pictures from the conference" title="pictures from the conference" /></a>
		<a href="#" class="thumb"><img src="<?php bloginfo('template_directory'); ?>/img/thumb_1.jpg" alt="pictures from the conference" title="pictures from the conference" /></a>
		<a href="#" class="thumb_last"><img src="<?php bloginfo('template_directory'); ?>/img/thumb_2.jpg" alt="pictures from the conference" title="pictures from the conference" /></a>
		<a href="#" class="thumb"><img src="<?php bloginfo('template_directory'); ?>/img/thumb_2.jpg" alt="pictures from the conference" title="pictures from the conference" /></a>
		<a href="#" class="thumb_last"><img src="<?php bloginfo('template_directory'); ?>/img/thumb_1.jpg" alt="pictures from the conference" title="pictures from the conference" /></a>
		<div class="clr"></div>
	</div>
	<?php endif; ?>
</div>
<!-- end sidebar -->
