=== Plugin Name ===
Contributors: radukn
Donate link: http://www.cnet.ro/wordpress/picasa-widget/
Tags: picasa, widgets, images, gallery
Requires at least: 2.5
Tested up to: 2.6
Stable tag: trunk

Picasa Widget works as a widget, making very easy for you to embed in sidebars (if supports widgets) the latest albums from your Picasa account, or the latest pictures, randomly from last n albums. Three ways of showing your albums!

== Description ==

This widget allows you to show pictures from your Picasa account in your blog's sidebar. Install the plugin and make the minimum configurations.

You will see that this plugin works in three modes.

1. Show the last n albums only as text (clickable titles).
2. Show the last n albums using thumbnails.
3. Show the last n pictures from the last m albums, randomly. If m is 1, than you will have randomly pictures from your last Picasa album.

If you don't want your Picasa pictures in sidebar, but shown as albums in your blog pages, use my other plugin [altPWA](http://wordpress.org/extend/plugins/altpwa/).

I was inspired by [Kevala](www.kivela.be/index.php/apps/wordpress-plugin-picasa-web-album-widget/) and [Thoughts of a codes](http://www.sandaru1.com/2007/07/11/picasa-widget-updated/) solutions.

== Installation ==

The plugin is simple to install:

1. Download the zip file
1. Unpack the zip. You should have a directory called `picasa-widget`, containing several files and folders
1. Upload the `picasa-widget` directory to the `wp-content/plugins` directory on your WordPress installation. 
1. Activate plugin
1. Go to Design -> Widgets for configuration. It's a must, before using the plugin.

== Frequently Asked Questions ==

= Can I show my last albums? =

Yes, each Picasa Web Album have a configurable thumbnail (on Picasa website). This can be used to show your last n albums.

= Can I show my last pictures? =

Yes, you can from the last album or from the last n albums (you choose).

= Can I choose how to show the pictures? =

Well, you have some options. It depends a lot on the size of your sidebar. You can have one column or many. And you choose how many pictures.

= Can I further customize the look of the the thumbnails? =

Yes, you have some options in the admin are of this plugin. And you can also use CSS.

= Why that specific sizes? =

This plugin use Picasa API and Picasa API use some standard sizes. Some are automaticaly squared, other not. We have to use those sizes.

= Do you use any caching mechanism? =

No. See [Thoughts of a codes](http://www.sandaru1.com/2007/07/11/picasa-widget-updated/) solution.

== Screenshots ==

1. You can see the many ways of showing pictures.
2. The configuration section for the widget.

== Documentation ==

Full documentation can be found on the [Picasa Widget](http://www.cnet.ro/wordpress/picasa-widget/) page.

== Changelog ==

1.2 [August 20, 2008]
- JScript solution removed, just PHP

1.1 [August 19, 2008]
- tested a PHP alternative to JScript solution

1.0 [August 18, 2008]
- first release