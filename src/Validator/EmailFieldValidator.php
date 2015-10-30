<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Org_Heigl\Wordpress\AjaxComment\Validator;

class EmailFieldValidator implements ValidatorInterface
{
    public function validate(array $values)
    {
        if (get_option('require_name_email')) {
            if (6 > strlen($values['email']['value'])) {
                $values['email']['errors'][] = __('The email-address is required',
                    'wp-ajax-comment');
            }
        }
        if ($values['email']['value'] && ! is_email($values['email']['value'])) {
            $values['email']['errors'][] = __('Please enter a valid email address',
                'wp-ajax-comment');
        }

        return $values;
    }
}