<?php

function se_html2rgb($color)
{
    if ($color[0] == '#')
        $color = substr($color, 1);

    if (strlen($color) == 6)
        list($r, $g, $b) = array($color[0].$color[1],
                                 $color[2].$color[3],
                                 $color[4].$color[5]);
    elseif (strlen($color) == 3)
        list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
    else
        return false;

    $r = hexdec($r); $g = hexdec($g); $b = hexdec($b);

    $rgb = $r.", ".$g.", ".$b;
    return $rgb;
}

 

$_GET['id'] = base64_decode($_GET['id']);
  $font = $_GET['font'];
  if ($font == 7){
    $font = imageloadfont("fonts/courier8.gdf");
  }
  elseif ($font == 8){
    $font = imageloadfont("fonts/proggyclean.gdf");
  }
  elseif ($font == 9){
    $font = imageloadfont("fonts/segoe.gdf");
  }
  elseif ($font == 10){
    $font = imageloadfont("fonts/reize.gdf");
  }
  elseif ($font == 11){
    $font = imageloadfont("fonts/9x15iso.gdf");
  }


$bgcolor = se_html2rgb($_GET['bg']);
$bgcolor = explode(", ", $bgcolor);

$ftcolor = se_html2rgb($_GET['ft']);
$ftcolor = explode(", ", $ftcolor);

$bdcolor = se_html2rgb($_GET['bd']);
$bdcolor = explode(", ", $bdcolor);

$image_height = intval(imageFontHeight($font));
$image_width = intval(strlen($_GET['id']) * imageFontWidth($font) *1.1);

$image = imageCreate($image_width, $image_height);

$back_color = imageColorAllocate($image, $bgcolor[0], $bgcolor[1], $bgcolor[2]);

$text_color = imageColorAllocate($image, $ftcolor[0], $ftcolor[1], $ftcolor[2]);

$rect_color = imageColorAllocate($image, $bdcolor[0], $bdcolor[1], $bdcolor[2]);

$x = ($image_width - (imageFontWidth($font) * strlen($_GET['id']))) / 2;
$y = ($image_height - imageFontHeight($font)) / 2 - 1;

imageString($image, $font, $x, $y, $_GET['id'], $text_color);
imageRectangle($image, 0, 0, imageSX($image) - 1, imageSY($image) - 1, $rect_color);


imagePNG($image);
imageDestroy($image);



?>