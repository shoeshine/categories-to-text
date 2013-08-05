Categories to Text
==================

ExpressionEngine 2.+ extension that puts all selected categories for an entry into a designated text channel field.


Installation
------------
1. Copy /system/third_party/categories_to_text/ to /system/expressionengine/third_party/
2. Enable the extension from Add-ons » Extensions


Settings
-----
Only Channels with an assigned category group AND a basic text fieldtype (text input, textarea) will appear.

 * Click "Add New Category-Field Pair" to define which category group to save to text field.
 * **Category Group:** For each entry in this channel, the selected categories (from this category group) will be saved to the selected **Text Field** as a comma-dilenated string.
 * **Text-Field:** Only the basic EE text input and textarea fields for this channel will appear as options.
 * **Synonym Text Field:** [Optional] If you have created a custom text field for the selected category group, it will appear. The string in that custom category field will be added to the selected **Text Field** along with the categories. This was initially created for adding search synonyms for specific categories.
 * **Update Existing [channel name] Channel Entries:** Check to have the extension update entries in the specific channel with the new data. Leave unchecked to leave entries as they are.
 * Click "Submit" to save any changes.

Usage
-----
The settings saved in the extension will be applied to an entry (new or edited).

If you are using [EE's native Search module](http://ellislab.com/expressionengine/user-guide/modules/search), you can use this extension to search categories via the **Text Field**.

Original purpose
----------------
We created this extension to combine with [Low Search](http://gotolow.com/addons/low-search) to search category names (and category synonyms) **with weighted values**. Instead of having Low Search index the categories themselves, we had it index the channel text field (which we hid from the client in the EE publish view). Many of the categories also had synonymous terms that needed to be equal to the main category name, which is why a selected custom category text field can be tacked on to the outputted string value to the channel text field. (With EE 2.7, Low Search will be updated to allow weighted category searching.)

We'd love hear how you're using this extension and how we can make it work even better for you.