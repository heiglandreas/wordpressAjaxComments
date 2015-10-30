=== wordpressAjaxComments ===
Contributors: heiglandreas
Tags: comment, comments, ajax, inline error
Requires at least: 4.3.0
Tested up to: 4.3.1
Stable tag: trunk
License: MIT
License URI: https://opensource.org/licenses/MIT

This plugin sends comments via AJAX and displays errors inline directly at the problematic form-field. Supports custom fields too.

== Description ==
Have you ever been annoyed that there are no inline-error messages for the comments? That the errors are displayed on a different page completely out of context?

Here\'s the answer. The comments are send via AJAX, errors that happen will be displayed inline in the form, you can style the error-messages yourself (if you want or have to) and it is even possible to add custom fields to the comment-form, provided the validation for those extra fields is done via the Ajax-Comments callback.

How is this plugin different from other Ajax-Comment-plugins?

This plugin uses a comletely rewritten backend and doesn\'t use the default wordpress-backend although it is based on that.
This plugin displays the errors directly at the field they happened at. Therefore the error-messages are right in the context of the fields.
This plugin uses the AJAX-functionality brought to you with the core wordpress, so there are no third-party libraries necessary
This plugin can handle custom fields in the form without a problem. If you want to use the error-display-functions of the plugin you will have to use the wp_ajax_comment_validate_form-filter and for storing those metadata-fields you will need to implement the wp_ajax_comment_store_metadata-action.
If you find any issues during the usage, feel free to create an issue on the issue tracker on github


== Installation ==
No special instructions are required.

* Search for the plugin in your wordpress-admin-backend
* Click on the install button
* Activate the plugin

There is no configuration necessary!