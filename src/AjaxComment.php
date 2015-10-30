<?php
/*
 * Copyright (c) 2015-2015 Andreas Heigl <andreas@heigl.org>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace Org_Heigl\Wordpress\AjaxComment;

use Org_Heigl\Wordpress\AjaxComment\Validator\CommentFieldValidator;
use Org_Heigl\Wordpress\AjaxComment\Validator\EmailFieldValidator;
use Org_Heigl\Wordpress\AjaxComment\Validator\UsernameFieldValidator;

class AjaxComment
{
    public function enqueue()
    {
        wp_enqueue_script('wp-ajax-comment', plugins_url( '/js/main.js', __DIR__), array('jquery'), '1.0.0', true );
        wp_enqueue_style('wp-ajax-comment-css', plugins_url( '/css/main.css', __DIR__), array(), '1.0.0', 'all' );
    }

    public function processCommentSubmission($values)
    {

        if ( 'POST' != $_SERVER['REQUEST_METHOD'] ) {
            header('Allow: POST');
            header('HTTP/1.1 405 Method Not Allowed');
            header('Content-Type: text/plain');
            exit;
        }

        $values = $_POST;

        try {
            $comment_post_ID = (isset($values['comment_post_ID'])) ? (int) $values['comment_post_ID'] : 0;

            $post = get_post($comment_post_ID);

            if (empty($post->comment_status)) {
                /**
                 * Fires when a comment is attempted on a post that does not exist.
                 *
                 * @since 1.5.0
                 *
                 * @param int $comment_post_ID Post ID.
                 */
                do_action('comment_id_not_found', $comment_post_ID);
                throw new Exception\UnknownPostCommentedException(sprintf(
                    __('The post with ID %s could not be found',
                        'wp-ajax-comment'),
                    $comment_post_ID
                ));
            }

            // get_post_status() will get the parent status for attachments.
            $status = get_post_status($post);

            $status_obj = get_post_status_object($status);

            if (! comments_open($comment_post_ID)) {
                /**
                 * Fires when a comment is attempted on a post that has comments closed.
                 *
                 * @since 1.5.0
                 *
                 * @param int $comment_post_ID Post ID.
                 */
                do_action('comment_closed', $comment_post_ID);
                throw new Exception\PostCommentDisabledException(sprintf(
                    __('Sorry, comments are closed for this item.',
                        'wp-ajax-comment'),
                    $comment_post_ID
                ));
            } elseif ('trash' == $status) {
                /**
                 * Fires when a comment is attempted on a trashed post.
                 *
                 * @since 2.9.0
                 *
                 * @param int $comment_post_ID Post ID.
                 */
                do_action('comment_on_trash', $comment_post_ID);
                throw new Exception\PostIsTrashedException(sprintf(
                    __('This post can not be commented as it is in trash',
                        'wp-ajax-comment'),
                    $comment_post_ID
                ));
            } elseif (! $status_obj->public && ! $status_obj->private) {
                /**
                 * Fires when a comment is attempted on a post in draft mode.
                 *
                 * @since 1.5.1
                 *
                 * @param int $comment_post_ID Post ID.
                 */
                do_action('comment_on_draft', $comment_post_ID);
                throw new Exception\PostIsDraftException(sprintf(
                    __('This post is a draft and can not be commented',
                        'wp-ajax-comment'),
                    $comment_post_ID
                ));
            } elseif (post_password_required($comment_post_ID)) {
                /**
                 * Fires when a comment is attempted on a password-protected post.
                 *
                 * @since 2.9.0
                 *
                 * @param int $comment_post_ID Post ID.
                 */
                do_action('comment_on_password_protected', $comment_post_ID);
                throw new Exception\PostIsPasswordProtectedException(sprintf(
                    __('This post is password-protected and can not be commented',
                        'wp-ajax-comment'),
                    $comment_post_ID
                ));
            } else {
                /**
                 * Fires before a comment is posted.
                 *
                 * @since 2.8.0
                 *
                 * @param int $comment_post_ID Post ID.
                 */
                do_action('pre_comment_on_post', $comment_post_ID);
            }
        } catch(\Exception $e) {
            return $this->sendErrorMessage($e);
        }

        // If the user is logged in
        $user = wp_get_current_user();
        if ($user->exists()) {
            if (empty( $user->display_name)) {
                $user->display_name = $user->user_login;
            }
            $values['author'] = wp_slash($user->display_name);
            $values['email']  = wp_slash($user->user_email);
            $values['url']    = wp_slash($user->user_url);
            if (current_user_can('unfiltered_html')) {
                if (! isset( $values['_wp_unfiltered_html_comment'])
                     || ! wp_verify_nonce($values['_wp_unfiltered_html_comment'], 'unfiltered-html-comment_' . $comment_post_ID)
                ) {
                    kses_remove_filters(); // start with a clean slate
                    kses_init_filters(); // set up the filters
                }
            }
        } else {
            if (get_option('comment_registration') || 'private' == $status) {
                $this->sendErrorMessage(new Exception\LoginRequiredForCommentException(
                    __('Sorry, you must be logged in to post a comment.', 'wp-ajax-comment')
                ));
            }
        }

        foreach($values as $key => $item) {
            $values[$key] = array(
                'value' => $item,
                'errors' => [],
            );
        }

        add_filter('wp_ajax_comment_validate_form', array(new EmailFieldValidator(), 'validate'));
        add_filter('wp_ajax_comment_validate_form', array(new UsernameFieldValidator(), 'validate'));
        add_filter('wp_ajax_comment_validate_form', array(new CommentFieldValidator(), 'validate'));

        $values = apply_filters('wp_ajax_comment_validate_form', $values);

        if ($this->hasErrors($values)) {
            return $this->sendErrors($values);
        }

        try {
            $comment = $this->storeComment($values);
        } catch (\Exception $e) {
            return $this->sendErrorMessage($e);
        }

        /**
         * Perform other actions when comment cookies are set.
         *
         * @since 3.4.0
         *
         * @param object $comment Comment object.
         * @param WP_User $user   User object. The user may not exist.
         */
        do_action('set_comment_cookies', $comment, $user);

        $location = empty($_POST['redirect_to']) ? get_comment_link($comment->comment_ID) : $_POST['redirect_to'] . '#comment-' . $comment->comment_ID;

        /**
         * Filter the location URI to send the commenter after posting.
         *
         * @since 2.0.5
         *
         * @param string $location The 'redirect_to' URI sent via $_POST.
         * @param object $comment  Comment object.
         */
        $location = apply_filters('comment_post_redirect', $location, $comment);

        header('Content-Type: application/json');

        echo json_encode(array('location'=>$location));

        // has to be 'exit' as otherwise we have a '0' as last char in the
        // response...
        exit;
    }

    protected function storeComment(array $values)
    {
        add_action('comment_duplicate_trigger', [$this, 'duplicateAction']);

        try {
            $commentdata = array(
                'comment_parent' => isset($values['comment_parent']['value']) ? absint($values['comment_parent']['value']) : 0,
                'comment_post_ID'      => $values['comment_post_ID']['value'],
                'comment_author'       => $values['author']['value'],
                'comment_author_email' => $values['email']['value'],
                'comment_author_uri'   => $values['uri']['value'],
                'comment_content'      => $values['comment']['value'],
                'comment_type'         => $values['comment_mail_sub_type']['value'],
                'user_ID'              => false,
            );

            $comment_id = wp_new_comment($commentdata);
            if (! $comment_id) {
                throw new Exception\CommentNotStoredException(
                    __('The comment could not be saved. Please try again later.',
                        'wp-ajax-comment')
                );
            }

            do_action('wp_ajax_comment_store_metadata', $values, $comment_id);

            return get_comment($comment_id);
        } catch (\Exception $e) {

            return $this->sendErrorMessage($e);
        }
    }

    protected function sendErrorMessage(\Exception $e)
    {
        header('HTTP/1.1 500 Server Error');
        header('Content-Type: application/json');

        $content = array(
            'error'   => $e->getCode(),
            'message' => $e->getMessage(),
        );

        echo json_encode($content);
        exit;
    }

    protected function sendErrors(array $values)
    {
        header('HTTP/1.1 400 Error');
        header('Content-Type: application/json');

        echo json_encode($values);
        exit;
    }

    protected function hasErrors(array $values)
    {
        foreach ($values as $value) {
            if ($value['errors']) {
                return true;
            }
        }

        return false;
    }

    public function addFields($fields)
    {
        $fields['action'] = '<input id="action" name="action" type="hidden" value="processCommentSubmission"/>';

        return $fields;
    }

    public function autoload($classname)
    {
        $classname = ltrim($classname, '\\');
        if(strpos($classname, __NAMESPACE__) !== 0)
            return;

        $classname = str_replace(__NAMESPACE__, '', $classname);

        $path = __DIR__ . DIRECTORY_SEPARATOR .
                str_replace('\\', DIRECTORY_SEPARATOR, $classname) . '.php';

        require_once $path;
    }

    public function duplicateAction($commentdata)
    {
        throw new Exception\CommentAlreadyExistsException('This comment already seems to exists');
    }
}