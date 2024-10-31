=== Plugin Name ===
Contributors: pacius
Donate link: http://www.pasi.fi/slightly-advanced-graphing-with-pasichart/
Tags: graph, chart, statistics
Requires at least: 2.0.1
Tested up to: 2.0.4
Stable tag: 0.1

Advanced charting. Write charts directly in your posts with simple scripting language! Very very alpha, but has some working features, such as the bonus feature: post and comment statistics.

== Description ==


== Installation ==

Requires that your PHP installation has GD support enabled.

Simply extract the plugin archive into your WordPress plugins directory and activate the plugin.

== Frequently Asked Questions ==

= Will there be any progress in the plugin development? =

I don't know. I hope to improve it some day. Now I simply don't have time and energy for that. Maybe someone else does something?

== Screenshots ==

1. Example chart with line and bar graphs.
2. Example blogging statistics.

== Creating blog statistics page ==

Create a page and insert PasiChart script similar to examples below:

[[PASICHART blog y][caption Blogging History at Pasi.fi (Yearly)][size 440,200]]

[[PASICHART blog monthly][caption Blogging History at Pasi.fi (Monthly)][size 440,200]]

[[PASICHART blog w 49][caption Last 7 Week Blogging History at Pasi.fi (Weekly)][size 440,200]]

[[PASICHART blog d 14][caption Last 14 Day Blogging History at Pasi.fi (Daily)][size 440,200]]

In the first option brackets, "blog" means that this chart is blogging stats bar graph. After that you can select stats type by typing either yearly, monthly, weekly or daily (you can abbreviate these as shown above). After this you can select the number of days included, counting backwards from today.

Second, there's caption brackets. Just write the caption you want or leave it out entirely to have no caption.

Finally, use the size brackets to determine the dimensions of the chart image. The size of the chart itself depends on the surrounding elements.