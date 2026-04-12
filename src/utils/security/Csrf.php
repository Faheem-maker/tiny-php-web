<?php

namespace framework\web\utils\security;

class Csrf
{
    public static function allocate()
    {
        if (app()->session->has('csrf_token')) {
            return app()->session->get('csrf_token');
        }
        if (function_exists('mcrypt_create_iv')) {
            $token = bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
        } else {
            $token = bin2hex(openssl_random_pseudo_bytes(32));
        }

        return app()->session->set('csrf_token', $token);
    }

    public static function validate($token)
    {
        if (!empty($token) && hash_equals(app()->session->get('csrf_token', ''), $token)) {
            app()->session->remove('csrf_token');
            return true;
        }

        return false;
    }
}