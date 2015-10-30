<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Org_Heigl\Wordpress\AjaxComment\Validator;

class CommentFieldValidator implements ValidatorInterface
{
    public function validate(array $values)
    {
        if (! $values['comment']['value']) {
            $values['comment']['errors'][] = __('Please type a comment', 'wp-ajax-comment');
        }

        return $values;
    }
}