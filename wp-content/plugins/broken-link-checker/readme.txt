=== Broken Link Checker ===
Contributors: whiteshadow
Tags: links, broken, maintenance, blogroll, custom fields, admin
Requires at least: 2.8.0
Tested up to: 3.0-alpha
Stable tag: 0.8.1

This plugin will check your posts, custom fields and the blogroll for broken links and missing images and notify you if any are found. 

== Description ==
This plugin will monitor your blog looking for broken links and let you know if any are found.

**Features**

* Monitors links in your posts, pages, the blogroll, and custom fields (optional).
* Detects links that don't work and missing images.
* Notifies you on the Dashboard if any are found.
* Also detects redirected links.
* Makes broken links display differently in posts (optional).
* Link checking intervals can be configured.
* New/modified posts are checked ASAP.
* You view broken links, redirects, and a complete list of links used on your site, in the *Tools -> Broken Links* tab. 
* Searching and filtering links by URL, anchor text and so on is also possible.
* Each link can be edited or unlinked directly via the plugin's page, without manually editing each post.

**Basic Usage**

Once installed, the plugin will begin parsing your posts, bookmarks (AKA blogroll), etc and looking for links. Depending on the size of your site this can take a few minutes or even several hours. When parsing is complete the plugin will start checking each link to see if it works. Again, how long this takes depends on how big your site is and how many links there are. You can monitor the progress and set various link checking options in *Settings -> Link Checker*.

Note : Currently the plugin only runs when you have at least one tab of the Dashboard open. Cron support will likely be added in a later version.

The broken links, if any are found, will show up in a new tab of WP admin panel - *Tools -> Broken Links*. A notification will also appear in the "Broken Link Checker" widget on the Dashboard. To save display space, you can keep the widget closed and configure it to expand automatically when problematic links are detected.

The "Broken Links" tab will by default display broken links that have been detected so far. However, you can use the subnavigation links on that page to view redirects or see a listing of all links - working or not - instead.

There are several actions associated with each link listed - 

* "Details" shows more info about the link. You can also toggle link details by clicking on the "link text" cell.
* "Edit URL" lets you change the URL of that link. If the link is present in more than one place (e.g. both in a post and in the blogroll) then all instances of that URL will be changed.
* "Unlink" removes the link but leaves the link text intact.
* "Exclude" adds the link's URL to the exclusion list. Excluded URLs won't be checked again.
* "Discard" lets you manually mark the link as valid. This is useful if you know it was detected as broken only due to a temporary glitch or similar. The link will still be checked normally later.

**Translations**

* Belorussian - [M. Comfi](http://www.comfi.com/)
* Chinese Simplified - [Hank Yang](http://wenzhu.org/)
* Danish - [Georg S. Adamsen](http://wordpress.blogos.dk/)
* Dutch - [Gideon van Melle](http://www.gvmelle.com/)
* French - [Whiler](http://blogs.wittwer.fr/whiler/)
* German - [Alex Frison](http://notaniche.com)
* Italian - [Gianni Diurno](http://gidibao.net/index.php/portfolio/) and [Giacomo Ross](http://www.luxemozione.com/) (alternative)
* Russian - [Anna Ozeritskaya](http://hweia.ru/)
* Spanish - [Omi](http://equipajedemano.info/)
* Ukrainian - [Stas Mykhajlyuk](http://www.kosivart.com/)

== Installation ==

To do a new installation of the plugin, please follow these steps

1. Download the broken-link-checker.zip file to your local machine.
1. Unzip the file 
1. Upload `broken-link-checker` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

That's it.

To upgrade your installation

1. De-activate the plugin
1. Get and upload the new files (do steps 1. - 3. from "new installation" instructions)
1. Reactivate the plugin. Your settings should have been retained from the previous version.

== Changelog ==

*This is an automatically generated changelog*

= 0.8.1 =
* Updated Italian translation.
* Removed the survey link.

= 0.8 =
* Initial support for performing some action on multiple links at once.
* Added a "Delete sources" bulk action that lets you delete all posts (or blogroll entries) that contain any of the selected links. Doing this in WP 2.9 and up this will instead move the posts to the trash, not delete them permanently.
* New bulk action : Unlink. Removes all selected links from all posts.
* New bulk action : Fix redirects. Analyzes the selected links and replaces any redirects with direct links.
* Added a notice asking the user to take the feedback survey.
* Update the .POT file with new i18n strings.

= 0.7.4 =
* Fixed a minor bug where the plugin would display an incorrect number of links in the "Displaying x-y of z" label when the user moves to a different page of the results.
* Added Ukrainian translation.

= 0.7.3 =
* Reverted to the old access-checking algorithm + some error suppression.

= 0.7.2 =
* Only use the custom access rights detection routine if open\_basedir is set.

= 0.7.1 =
* Updated Russian translation.
* Yet another modification of the algorithm that tries to detect a usable directory for the lockfile.

= 0.7 =
* Added a Search function and the ability to save searches as custom filters
* Added a Spanish translation
* Added a Belorussian translation
* Added an option to add a removed\_link CSS class to unlinked links
* Slight layout changes
* Added localized date display (where applicable)
* The background worker thread that is started up via AJAX will now close the connection almost immediately after it starts running. This will reduce resource usage slightly. May also solve the rare and mysterious slowdown some users have experienced when activating the plugin.
* Updated Italian translation
* Fixed an unlocalized string on the "Broken Links" page

= 0.6.5 =
* Added Russian translation.

= 0.6.4 =
* Added French translation.
* Updated Italian translation.

= 0.6.3 =
* Added a German translation.

= 0.6.2 =
* Added an Italian translation.
* Added a Danish translation.
* Added a Chinese (Simplified) translation.
* Added a Dutch translation.

= 0.6.1 =
* Some translation-related fixes.

= 0.6 =
* Initial localization support.

= 0.5.18 =
* Added a workaround for auto-enclosures. The plugin should now parse the "enclosure" custom field correctly.
* Let people use Enter and Esc as shortcuts for "Save URL" and "Cancel" (respectively) when editing a link.

= 0.5.17 =
* Added a redirect detection workaround for users that have safe\_mode or open\_basedir enabled.

= 0.5.16.1 =
* Be more careful when parsing safe\_mode and open\_basedir settings.

= 0.5.16 =
* Also try the upload directory when looking for places where to put the lockfile.

= 0.5.15 =
* Editing links with relative URLs via the plugin's interface should now work properly. Previously the plugin would just fail silently and behave as if the link was edited, even if it wasn't.

= 0.5.14 =
* Made the timeout value used when checking links user-configurable.
* The plugin will now report an error instead of failing silently when it can't create the necessary database tables.
* Added a table listing assorted debug info to the settings page. Click the small "Show debug info" link to display it.
* Cleaned up some redundant/useless code.

= 0.5.13 =
* Fixed the bug where the plugin would ignore FORCE\_ADMIN\_SSL setting and always use plain HTTP for it's forms and AJAX.

= 0.5.12 =
* Let the user set a custom temporary directory, if the default one is not accessible for some reason.

= 0.5.11 =
* Use absolute paths when loading includes. Apparently using the relative path could cause issues in some server configurations.

= 0.5.10.1 =
* Fix a stupid typo

= 0.5.10 =
* Separated the user-side functions from the admin-side code so that the plugin only loads what's required.
* Changed some internal flags yet again.
* Changed the algorithm for finding the server's temp directory.
* Fixed the URL extraction regexp again; turns out backreferences inside character classes don't work.
* Process shortcodes in URLs.
* If the plugin can't find a usable directory for temporary files, try wp-content.
* Don't remove <pre> tags before parsing the post. Turns out they can actually contain valid links (oops).

= 0.5.9 =
* Added an autogenerated changelog.
* Added a workaround to make this plugin compatible with the SimplePress forum.
* Fixed <pre> block parsing, again.
* Fixed a bug where URLs that only differ in character case would be treated as equivalent.
* Improved the database upgrade routine.

= 0.5.8.1 =
* Added partial proxy support when CURL is available. Proxies will be fully supported in a later version.

= 0.5.8 =
* Fixed links that are currently in the process of being checked showing up in the "Broken links" table.
* The post parser no longer looks for links inside <pre></pre> blocks.

= 0.5.7 =
* Slightly changed the dashboard widget's layout/look as per a user's request.

= 0.5.6 =
* Improved relative URL parsing. The plugin now uses the permalink as the base URL when processing posts.

= 0.5.5 =
* Minor bugfixes
* URLs with spaces (and some other special characters) are now handled better and won't get marked as "broken" all the time.
* Links that contain quote characters are parsed properly.

= 0.5.4 =
* Fixed the uninstaller not deleting DB tables.
* Other uninstallation logic fixes.

= 0.5.3 =
* Improved timeout detection/handling when using Snoopy.
* Set the max download size to 5 KB when using Snoopy.
* Fixed a rare bug where the settings page would redirect to the login screen when saving settings.
* Removed some stale, unused code (some still remains).

= 0.5.2 =
* Fixed a SQL query that had the table prefix hard-coded as "wp\_". This would previously make the plugin detect zero links on sites that have a different table prefix.

= 0.5.1 =
* Fix a bug when the plugin creates a DB table with the wrong prefix.

= 0.5 =
* This is a near-complete rewrite with a lot of new features. 
* See �http://w-shadow.com/blog/2009/05/22/broken-link-checker-05/ for details.

= 0.4.14 =
* Fix false positives when the URL contains an #anchor

= 0.4.13 =
* (Hopefully) fix join() failure when Snoopy doesn't return any HTTP headers.

= 0.4.12 =
* *There are no release notes for this version*

= 0.4.11 =
* Set the Referer header to blog's home address when checking a link. This should help deal with some bot traps.
* I know, I know - there haven't been any major updates for a while. But there will be eventually :)
* Fix SQL error when a post is deleted.

= 0.4.10 =
* Changed required access caps for "Manage -> Broken Links" from manage\_options to edit\_ohers\_posts. This will allow editor users to access that page and it's functions.

= 0.4.9 =
* Link sorting, somewhat experimental.
* JavaScript sorting feature for the broken link list.

= 0.4.8 =
* CURL isn't required anymore. Snoopy is used when CURL isn't available.
* Post title in broken link list is now a link to the post (permalink). Consequently, removed "View" button.
* Added a "Details" link. Clicking it will show/hide more info about the reported link.
* "Unlink" and "Edit" now work for images, too. "Unlink" simply removes the image.
* Database modifications to enable the changes described above.
* Moved the URL checking function from wsblc\_ajax.php to broken-link-checker.php; made it more flexible.
* New and improved (TM) regexps for finding links and images.
* A "Settings" link added to plugin's action links.
* And probably other stuff I forgot!
* Grr :P

= 0.4.7 =
* Minor enhancements : 
* Autoselect link URL after the user clicks "Edit".
* Make sure only HTTP and HTTPS links are checked.
* More substantive improvements will hopefully follow next week.

= 0.4.6 =
* Minor compatibility enhancement in wsblc\_ajax.php - don't load wpdb if it's already loaded.

= 0.4.5 =
* Bugfixes. Nothing more, nothing less.
* Revisions don't get added to the work queue anymore.
* Workaround for rare cURL timeout bug.
* Improved WP 2.6 compatibility.
* Correctly handle URLs containing a single quote '.

= 0.4.4 =
* Consider a HTTP 401 response OK. Such links won't be marked as broken anymore.

= 0.4.3 =
* Fix : Don't check links in revisions, only posts/pages.

= 0.4.2 =
* *There are no release notes for this version*

= 0.4.1 =
* Split translated version from the previous code. Was causing weird problems.

= 0.4-i8n =
* *There are no release notes for this version*

= 0.4 =
* Added localization support (may be buggy).

= 0.3.9 =
* Fix : Use get\_permalink to get the "View" link. Old behavior was to use the GUID.

= 0.3.8 =
* Edit broken links @ Manage -> Broken Links (experimental)

= 0.3.7 =
* Change: A bit more verbose DB error reporting for the "unlink" feature.

= 0.3.6 =
* Switch from wp\_print\_scripts() to wp\_enqueue\_script()
* Wp\_enqueue\_script()

= 0.3.5 =
* New: "Delete Post" option.
* New: Increase the compatibility number.
* Change: Default options are now handled in the class constructor.

= 0.3.4 =
* Ignore mailto: links
* Ignore links inside <code> blocks

= 0.3.3 =
* *There are no release notes for this version*

= 0.3.2 =
* Fix Unlink button not working, some other random fixes

= 0.3.1 =
* *There are no release notes for this version*

= 0.3 =
* *There are no release notes for this version*

= 0.2.5 =
* Applied a small patch @ 347
* Fix some omissions
* Lots of new features in version 0.3

= 0.2.4 =
* Bigfix - use GET when HEAD fails

= 0.2.3 =
* MySQL 4.0 compatibility + recheck\_all\_posts function

= 0.2.2.1 =
* *There are no release notes for this version*

= 0.2.2 =
* *There are no release notes for this version*

= 0.2 =
* *There are no release notes for this version*

= 0.1 =
* *There are no release notes for this version*
