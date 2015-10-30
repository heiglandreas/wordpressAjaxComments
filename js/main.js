/**
 * Created by heiglandreas on 28.10.15.
 */
jQuery( document ).ready(function( $ ) {
    $('#commentform').on('submit',function(e){
        e.stopPropagation();
        e.preventDefault();

        // POST to new endpoint and either redirect to the new URI or display
        // error messages
        var myForm = $(e.target);

        $.ajax({
            type: "POST",
            url: "wp-admin/admin-ajax.php",
            data: myForm.serialize(),
            success: function(a){
                // no error, therefore a new URI is sent as JSON-value
                window.location.href = a.location;
                window.location.reload(true);
            },
            error: function(a, b, c){
                wp_remove_form_error_messages();
                if (a.status == 400) {
                    var response = jQuery.parseJSON(a.responseText);
                    for (i in response) {
                        if (response[i].errors.length == 0) {
                            continue;
                        }
                        wp_set_form_error_message(i, response[i].errors);
                    }
                    wp_set_form_error_message('', ['Please fix these errors']);
                } else if (a.status == 500) {
                    content = jQuery.parseJSON(a.responseText);
                    wp_set_form_error_message('', [content.message]);
                }
                var values = a.responseText
                // remove all errormessages
                // Show the error messages according to the fields.
            }
        });
    });
});

wp_remove_form_error_messages = function() {
    jQuery('#commentform').each(function(){
        jQuery(this).find(':input').each(function(){
            jQuery(this).removeClass('error');
            var next = jQuery(this).next();
            if (! next.hasClass('errormessage')) {
                return;
            }
            next.remove();
        });
        jQuery(this).find('ul.formerrormessage').each(function(){
            jQuery(this).remove();
        });
    });
}

wp_set_form_error_message = function(inputname, messages) {
    if (! inputname) {
        jQuery('#commentform').each(function(){
            jQuery(this).prepend(function(){
                var string = '<ul class="formerrormessage">';
                for (i in messages) {
                    string = string + '<li>' + messages[i] + '</li>';
                }
                return string + '</ul>';
            });
        });
        return;
    }
    var elem = jQuery('[name='+inputname+']');
    elem.addClass('error');
    elem.after(function(){
        var string = '<ul class="errormessage">';
        for (i in messages) {
            string = string + '<li>'+messages[i]+'</li>';
        }
        return string + '</ul>';
    });
}