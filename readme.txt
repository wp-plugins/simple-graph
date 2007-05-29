=== Plugin Name ===
Contributors: pacius
Tags: graph, chart, weight loss, widget
Requires at least: 2.0.1
Tested up to: 2.2.0
Stable tag: 0.9.8c

Draws a line graph of single set of date related data. Graph can be made public (i.e. sidebar widget) and the data can be edited through dashboard.

== Description ==

Draws a graph of single set of date related data. Graph can be made public (i.e. sidebar widget) and the data can be manipulated through dashboard. Many people use this plugin for weight tracking on their blogs (as do I), but it has been used for many other things as well. Only requirement - or rather a constraint - is that the horizontal axis of the graph shows dates. The vertical axis can hold any values.

== Installation ==

Requires that your PHP installation has GD support enabled. The plugin's configuration page shows information on this.

First of all, backup your Wordpress files and database! Installation of this plugin shouldn't do any harm, but since I've done absolutely no testing besides using it on my WP 2.0.3 platform, I cannot guarantee it's entirely bug free. Also, this is my first ever WP plugin, which I created both for fun and to learn the art of making plugins. So, this plugin is provided AS IS, and installation and usage of this plugin is entirely at your own risk. I will not assume any responsibility for any possible damages. (Although it still isn't supposed to cause any damages.)

Extract the zip archive in your Wordpress plugins folder (wp-content/plugins/) and then activate the plugin through your Wordpress dashboard. The activation sets up initial configuration options and creates the database table for the plugin data.

Please make sure that you upload/extract the simple-graph folder entirely in your plugin folder, and not just the contents of it! Your folder structure should look like this:

    * wp-content/plugins/
          o simple-graph/
                + pjm_graph.php
                + grapher/
                      # graph.php

Finally, insert a code similar to the one below to your Wordpress theme, for example in the sidebar.php file. The essential part is calling the pjm_graph() function. Note! If you are using widgets, of course you don't need to edit any files.

`<?php if (function_exists('pjm_graph')) { ?>
<li><h2>The Project</h2>
<?php pjm_graph(); ?><br />
<a href="http://www.pasi.fi/simple-graph-wordpress-plugin/">About this graph plugin…</a>
</li>
<?php } ?>`

Installation is now complete!

There are some parameters to that function though, so if you want to make your graph look different, you might want to check these out.

You can override width and height of the graph with function parameters. Also, as of v0.9.3 it is possible to add optional trend graph with a boolean flag. In version 0.9.6 further parameters (target, ytd, lm, and wkly) were added. See the function declaration below.

void pjm_graph($WIDTH, $HEIGHT, $TREND, $TARGET, $YTD, $MTD, $WKLY);

Zero (0) values for WIDTH and HEIGHT preserve the default width and height that are specified in the admin panel. Any other values override the default.

TREND, TARGET, YTD, MTD and WKLY parameters expect a boolean value, which is either TRUE or FALSE. By default they're all FALSE. If TREND is TRUE, gliding trend graph appears. If YTD is TRUE, only the values from last year are used in the graph. If MTD is TRUE, only the values from last month are used. (If YTD is TRUE, value of MTD is irrelevant.) If WKLY is TRUE, rough weekly average values will be calculated instead of daily values where possible. TARGET is not fully implemented yet and thus its value has no meaning at the moment.

== Frequently Asked Questions ==

= How about multiple graphs? =

If a 1.0 release is ever finished, it will support multiple graphs. However, don't hold your breath.

== Screenshots ==

1. Data management page
2. Widget control panel
3. Widget in action

== Change log ==

= 0.9.9 =

* Added multiple user-sensitive graphs, i.e. each user may have their own graphs
* Added content filter which allows insertion of graphs to pages and posts

= 0.9.8c =

* Fixed user roles issue, which broke the plugin in WordPress 2.2.

= 0.9.8b =

* Fixed broken graph image for sites that use different site and WordPress addresses.

= 0.9.8 =

* Added configurable title and text to widget. Both can contain wildcards which are replaced by values such as highest value, lowest value, et cetera.
* Extended widget control panel significantly.
* Removed old plugin configuration page as it was redundant.
* Bug fix: plugin now works even if plugin folder name is changed.

== Content filter ==

If you want to insert a graph in posts and/or pages, simply write a string similar to following in your post or page.

[[simple-graph x=0 y=0 trend=0 wkly=0 lm=0 ytd=0 uid=0 gid=0 ]]

Just replace the values as you wish. The ones above are default values, and if you don't need to change the default value, you can simply omit that value from the string. I.e. [[simple-graph]] alone produces the graph with default values drawn from widget setup.

* x is width, any positive value is acceptable
* y is height, any positive value is acceptable
* trend is whether trend graph is shown, 0 = no, 1 = yes
* wkly is whether weekly averages are shown, 0 = no, 1 = yes
* lm is whether only values from last month are shown, 0 = no, 1 = yes
* ytd is whether only values from last year are shown, 0 = no, 1 = yes
* uid is user ID of the graph owner (see WordPress dashboard -> Users)
* gid is user-specific graph number, same as in widget control panel's graph# selection
