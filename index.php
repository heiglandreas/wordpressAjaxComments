<?php
/*
Plugin Name: Wordpress Ajax Comments
Plugin URI: https://github.com/heiglandreas/wordpressAjaxComments
Description: This plugin enables sending coment-forms via ajax and displaying errors inline in the form
Version: 1.0.0
Author: Andreas Heigl <andreas@heigl.org>
Author URI: http://andreas.heigl.org
Text Domain: wp-ajax-comment
Domain Path: languages
License: MIT-License
License URI: https://opensource.org/licenses/MIT
*/
require_once __DIR__ . '/src/AjaxComment.php';

$ac = new \Org_Heigl\Wordpress\AjaxComment\AjaxComment();

add_action('wp_enqueue_scripts', [$ac, 'enqueue']);
add_action('wp_ajax_nopriv_processCommentSubmission', [$ac, 'processCommentSubmission']);
add_action('wp_ajax_processCommentSubmission', [$ac, 'processCommentSubmission']);
add_filter('comment_form_default_fields', [$ac, 'addFields']);

spl_autoload_register([$ac, 'autoload']);