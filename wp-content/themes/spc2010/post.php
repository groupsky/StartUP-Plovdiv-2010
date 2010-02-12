<div class="news" id="post-<?php the_ID(); ?>">
  <?php if (!is_single() &&!is_page()): ?>
    <h2><a href="<?php the_permalink() ?>" rel="bookmark"><?php the_title(); ?></a></h2>
  <?php the_date('','<p class="date">','</p>'); ?>
  <?php else: ?>
    <h2><?php the_title(); ?></h2>
  <?php endif; ?>
  <div class="meta"><?php edit_post_link(__('Edit This')); ?></div>
 
  <?php if (!is_page()): ?>
  <div class="share">
    <ul>
      <li><fb:share-button class="url" href="<?php the_permalink() ?>" type="button"></fb:share-button></li>
      <!--li>
        <a name="fb_share" type="button" href="<?php the_permalink() ?>">Споделяне</a>
        <script src="http://static.ak.fbcdn.net/connect.php/js/FB.Share" type="text/javascript"></script>
      </li-->
      <?php if (function_exists('tweetmeme')): ?>
        <li><?php echo tweetmeme(); ?></li>
      <?php endif; ?>
    </ul>
  </div>
  <div class="clr"></div>
  <?php endif; ?>
  <hr />

  <?php the_content(__('(more...)')); ?>

  <hr />
</div>

