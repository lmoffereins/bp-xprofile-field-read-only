=== BP XProfile Field Read Only ===
Contributors: Offereins
Tags: buddypress, xprofile, field, read, admin, only, readonly, disable, disabled, hide
Requires at least: 4.0, BP 2.2
Tested up to: 4.9, BP 3.0
Stable tag: 1.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Make BuddyPress XProfile fields uneditable or hidden for non-admins.

== Description ==

Use this plugin when you want to make certain profile fields uneditable for your members: the fields are removed from the edit context. Or hide the profile field fully for non-administrator members. Only users who have administrator rights can edit these profile fields.

== Installation ==

If you download BP XProfile Field Read Only manually, make sure it is uploaded to "/wp-content/plugins/bp-xprofile-field-read-only/".

Activate BP XProfile Field Read Only in the "Plugins" admin panel using the "Activate" link. If you're using WordPress Multisite, you can choose to activate BP XProfile Field Read Only network wide for full integration with all of your sites.

== Changelog ==

= 1.2.0 =
* Added option to make a field fully admin-only by removing it from both the field's editing and viewing context

= 1.1.1 =
* Fixed loading of translation files

= 1.1.0 =
* Removed the input attribute approach: disabled inputs are not processed by forms, so their values are set to empty!
* Changed option to do read-only by removing the field completely from edit contexts
* Changed plugin admin setting interface to a field metabox
* Added read-only-ability for the primary field

= 1.0.2 =
* Minor fixes

= 1.0.1 =
* Fixed read-only field inputs on registration page
* Improved styling of the admin metabox setting
* Added Dutch translation

= 1.0.0 =
* Initial release
