=== TweetMeme Button ===
Contributors: dtsn
Tags: twitter, retweet, voting, button, tweetmeme
Requires at least: 2.7.2
Tested up to: 2.9.1
Stable tag: 1.7.5

== Description ==

The TweetMeme retweet button easily allows your blog to be retweeted. The button also provides a current count of how many times your story has been retweeted throughout twitter.


Features

* New - Integration with TweetMeme Analytics
* Improved title support
* You can now see the tweet stats for the last 5 posts
* Quicker loading times for the buttons
* You can now change the URL shortner you are using!
* Integrates with Wordpress MU
* Allows you to change the source which you retweet, E.g. "RT @yourname <the title> <the url>"
* Integrates the ability to retweet your post with one click
* Shows the current number of times your post has been retweeted on twitter
* Easily installed
* Allows you to specify when the button should go (e.g. top or bottom)


== Installation ==

Follow the steps below to install the plugin.

1. Upload the TweetMeme directory to the /wp-content/plugins/ directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings/tweetmeme to configure the button

== Help ==

For help and support please refer to the TweetMeme help section at <a href="http://help.tweetmeme.com">help.tweetmeme.com</a>.

== Changelog ==

= 1.7.5 =

* Users were getting confused to what the API field does, updated the documentation

= 1.7.4 =

* Tested and works with version 2.9.1

= 1.7.3 =

* Changed line 101 (get_post_meta) to compare against null instead of empty string due to the new way Wordpress 2.9 returns meta_data

= 1.7.2 =

* Fixed the validation errors. Replaced '&' with '&amp;'
* Add a strip_tags to the meta title output, some plugins where causing tags to be outputted in the title
