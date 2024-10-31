=== ProgressFly ===
Contributors: damselfly
Donate link: http://deborahmcdonnell.com/
Tags: progress, meter
Requires at least: 2.7
Tested up to: 2.7
Stable tag: 0.61

Create customisable progress meters to track your projects to completion.

== Description ==

ProgressFly creates one or more progress meters to track your projects. Projects are managed (and customised) through the wordpress database. The meter is styled using CSS, so is highly customisable. Display a dynamic progress meter in your sidebar, or embed a (static or dynamic) progress meter in a post or page.

== Installation ==

1. Upload `progressfly.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Place a function call where you want the project meter/s to appear. For a list of available functions and their variables, see "ProgressFly Functions.htm" which is included in the download zip.

== Frequently Asked Questions ==

= Can I use decimals? =

Yes!

= Can ProgressFly only track words? =

No! ProgressFly ships with a Units field (which you can set for each progress meter you create). Specify whatever units you want here, whether it be words, pages, stitches, dollars, kilograms...

= Do I have to be able to edit my template to use ProgressFly? =

At the moment, ProgressFly is not widgetised. If you wish to display a progress bar in your sidebar, you will need to edit your template.

If you wish to embed a progress meter in a page or a post, you do not need to edit your templates, nor to embed executable php code into your post, as ProgressFly ships with an embedding function. See the list of function calls possible.

= I don't like the black and white defaults - can I change them? =

Of course. Since version 0.60, ProgressFly ships with a subpage under the Options menu. Here you can change the global defaults to whatever you prefer. These defaults will affect every new meter you create although, as always, you can change the defaults on an individual meter without changing the defaults.

= I don't have WordPress 2.7 installed; can I use ProgressFly? =

Due to a change in the WordPress architecture, ProgressFly v0.61 can only be used on WordPress 2.7. 

If you don't have WordPress 2.7 installed, I would recommend upgrading to 2.7 for security reasons. 

If you can't or won't upgrade, there's a simple hack: open up bibliofly.php, navigate to lines 357, 358, 402 and 567, and in each line change "tools.php" to "edit.php". 


== Screenshots ==

1. Demo progress meter, showing the default layout (title above, progress statistics below).
2. Demo progress meter, showing the "bare" layout (title above, progress percentage within, no units). The "preview" layout omits the title and progress statistics entirely.

