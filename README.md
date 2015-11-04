# Wordpress Ajax Comments

Submit comments in wordpress via AJAX

This allows users to get error-messages displayed right in the form at the place
they have to be fixed.

This plugin allows you also to extend the comment-form with your own form-elements.

As the validation is different from the default wordpress-way (which doesn't show
all errors but breaks at the first one) the validation of extended forms might not
work as expected when you use a plugin to extend the comment form.

There are the following actions and filters

* Action wp_ajax_comment_store_metadata(comment_id, array) To store metadata
* Filter wp_ajax_comment_validate_form(array) To validate the form-data

The array passed to the callbacks is an associative array of the following form:

    array(
        '<name of the form-field>' => array(
            'value' => <The value of the form field>
            'errors' => array()
        ),
        …
        'errors' => array()
    )

The ```errors```-Array contains a list of error-messages that will either be
displayed at the form-field (in case of errors for a certain field) or at the
top of the form (in case of errors in the "top"-errors-array)