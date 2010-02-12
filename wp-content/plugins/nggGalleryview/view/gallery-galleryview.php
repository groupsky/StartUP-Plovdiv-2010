<?php 
/**
Template Page for the jQuery Galleryview integration

Follow variables are useable :

	$gallery     : Contain all about the gallery
	$images      : Contain all images, path, title
	$pagination  : Contain the pagination content

 You can check the content when you insert the tag <?php var_dump($variable) ?>
 If you would like to show the timestamp of the image ,you can use <?php echo $exif['created_timestamp'] ?>
**/

?>
<?php if (!defined ('ABSPATH')) die ('No direct access allowed'); ?><?php if (!empty ($gallery)) : ?>

<?php /*
<div id="<?php echo $gallery->anchor ?>" class="galleryview">
	<!-- Thumbnails -->
	<?php foreach ($images as $image) : ?>		
	<div class="panel">
		<img src="<?php echo $image->imageURL ?>" />
		<div class="panel-overlay">
			<h2><?php echo html_entity_decode ($image->alttext); ?></h2>
			<p><?php echo html_entity_decode ($image->description); ?></p>
		</div>
	</div>
 	<?php endforeach; ?>
  	<ul class="filmstrip">
  	<?php foreach ($images as $image) : ?>	
	    <li><img src="<?php echo $image->thumbnailURL ?>" alt="<?php echo $image->alttext ?>" title="<?php echo $image->alttext ?>" /></li>
	<?php endforeach; ?>
  	</ul>

</div>

<script type="text/javascript" defer="defer">
	jQuery("document").ready(function(){
		jQuery('#<?php echo $gallery->anchor ?>').galleryView({
			panel_width: 940,
			panel_height: 200,
			frame_width: 94,
			frame_height: 20,
			transition_interval: 7500,
			overlay_color: '#222',
			overlay_text_color: 'white',
			caption_text_color: '#222',
			background_color: 'transparent',
			border: 'none',
			nav_theme: 'dark',
			easing: 'easeInOutQuad',
			pause_on_hover: true
		});
	});
	
</script>
*/?>

<div id="<?php echo $gallery->anchor ?>" class="carouselview">
  <div class="gallerycover">
    <div class="mygallery">
    	<ul>
      	<?php foreach ($images as $image) : ?>	
    	    <li><img src="<?php echo $image->imageURL ?>" alt="<?php echo $image->alttext ?>" title="<?php echo $image->alttext ?>" /></li>
      	<?php endforeach; ?>
    	</ul>
    </div>
  </div>

  <div class="controls">
    <ul class="numbers">
    	<?php
    	  $idx = 0;
    	  foreach ($images as $image): 
    	    $idx++;
    	?>	
  	    <li class="<?php echo $idx . ($idx==1?' current':''); ?>"><a href="#"><?php echo $idx; ?></a></li>
     	<?php endforeach; ?>
    </ul>
  </div>
	<!-- Texts -->
	<div class="textcover">
	  <div class="mytext">
	    <ul>
      	<?php 
      	  $idx = 0;
      	  foreach ($images as $image): 
      	    $idx++;
      	?>
      	  <li id="mytext-<?php echo $idx; ?>">
			      <h2><?php echo html_entity_decode ($image->alttext); ?></h2>
			      <p><?php echo html_entity_decode ($image->description); ?></p>
      	  </li>
       	<?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<script type="text/javascript" defer="defer">
	jQuery("document").ready(function(){
		jQuery('#<?php echo $gallery->anchor ?> .mygallery').jCarouselLite({
			easing: 'easeInOutQuad',
			visible: 1,
      vertical: true,
      speed: 1000,
      auto: 5000,
      circular: true,
      btnGo: [
      	<?php
      	  $idx = 1;
      	  foreach ($images as $image): 
      	    if ($idx > 1) echo ",";
      	?>	
    	    ".numbers .<?php echo $idx; ?>"
      	<?php 
      	    $idx++;
      	  endforeach; 
      	?>
      ]
    });
		jQuery('#<?php echo $gallery->anchor ?> .mytext').jCarouselLite({
      visible: 1,
      vertical: true,
			easing: 'easeInOutQuad',
      speed: 1000,
      auto: 5000,
      circular: true,
      btnGo: [
      	<?php
      	  $idx = 1;
      	  foreach ($images as $image): 
      	    if ($idx > 1) echo ",";
      	?>	
    	    ".numbers .<?php echo $idx; ?>"
      	<?php 
      	    $idx++;
      	  endforeach; 
      	?>
      ],
      beforeStart: function(a){
        //jQuery('.controls .numbers .' + a.get(0).id.substring(7)).removeClass('current');
      },
      afterEnd: function(a){
        jQuery('.controls .numbers li').removeClass('current');
        jQuery('.controls .numbers .' + a.get(0).id.substring(7)).addClass('current');
      }
    });
	});
</script>


<?php endif; ?>
