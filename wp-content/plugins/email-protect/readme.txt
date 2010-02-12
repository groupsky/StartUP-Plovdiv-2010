=== Plugin Name ===
Contributors: johnkolbert
Donate link: http://simply-basic.com/
Tags: email, obfuscate, spam
Requires at least: 2.3
Tested up to: 2.5.1
Stable tag: 1.0.1

Automatically protects email address in posts, pages, and comments by converting them into safe alternatives (such as an images or obfuscated text) to prevent spam harvesters from collecting them. 

== Description ==

The Email Protect WordPress plugin protects email addresses from being harvested from spam robots by converting them into forms that aren’t recognized. With Email Protect you can choose to obfuscate your email addresses in text form or image form automatically.

Features:

-Automatically protect email addresses from spam harvesters in posts, pages, and comments
-Obfuscate email addresses using text (myemail [at] example [dot] com) or images
-Control email text replacement for the "@" sign and final period in emails
-Control font, font color, background color, and border color of obfuscated email images
-Convert any email address in your template into obfuscated text or images by inserting a simple function call into your pages

== Installation ==

1. Download "emailprotect.zip" and unzip it.

2. Upload the entire plugin folder to wp-content/plugins/ and activate from the Plugin administrative menu.

3. Before the plugin will work you must enter your settings under "Email Protect" in the "Settings" menu (also called "options" in pre-2.5 WordPress versions)

Upgrade:

Use the automatic upgrade feature. Otherwise download a fresh copy and simply replace your current one.

== Frequently Asked Questions ==

Template Usage:

Email addresses are automatically obfuscated in posts, pages, and comments. You can chose the obfuscation method (text or image) under the "Settings" menu. Email addresses in comments are always obfuscated using the text method.

To obfuscate an email address directly in a template file, enter a PHP code that mimicks the following style:
`<?php if(function_exists('ep_email_protect')){ ep_email_protect($email, $type); } ?>`

The $email variable holds your email address and the $type is an optional variable that can either be "text" or "image" depending on the type of obfuscation you want. If you don't specify a type the default setting in the options menu is used. For example, if your email address is myemail@example.com and you want it converted into an image, you would enter the following into your theme's template file:
`<?php if(function_exists('ep_email_protect')){ ep_email_protect('myemail@example.com', 'image'); } ?>`

If you wanted text obfuscation, simply change "image" to "text." It is important to remember the single quotes around both the email address and the type in the above function. The function above is designed so that if you deactivate the plugin it will not break your theme.

== Screenshots ==

1. An example of an email address converted into an image.