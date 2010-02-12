<?php
/**
 * Startup Conference Plovdiv 2010
 */
get_header();
$first = true;
?>
    <!-- main content -->
      <?php if(is_home()): ?>
        <div id="main_col">
      <?php
          $first = false;
          include(TEMPLATEPATH . '/accent.php');
        endif;
        if (have_posts()) : 
          while (have_posts()) : 
            the_post(); 
            if ($first):
              if (is_page() && $post->ID == 2): ?>
                <div id="one_col">
            <?php else: ?>
                <div id="main_col">
            <?php 
              endif;
              $first = false;
            endif;
            include(TEMPLATEPATH . '/post.php');
          endwhile; 
          posts_nav_link(' &#8212; ', __('&laquo; Newer Posts'), __('Older Posts &raquo;'));
        else: 
      ?>
      <p><?php _e('Sorry, no posts matched your criteria.'); ?></p>
      <?php endif; ?>
    </div>
    <!-- /main content -->

    <?php 
      if (!is_page() || $post->ID != 2):
        get_sidebar(); 
      endif;
    ?>
    <div class="clr"></div>
  </div>
<?php if(!is_home() && !is_category('preporachvame') && !is_page('video')): ?>
    <div id="movies">
<div style="margin: 0 auto; width: 940px;">
      <?php 
        if (function_exists('dynamic_sidebar'))
          dynamic_sidebar('footer-1');
      ?>
</div>
    </div>
    <?php endif; ?>
</div>
<?php get_footer(); ?>
