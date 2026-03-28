<?php

namespace framework\web\components;

use framework\Component;

class Session extends Component
{
    public function init(): void
    {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => config('security.HTTPS', true),     // HTTPS only
            'httponly' => true,   // no JS access
            'samesite' => 'Strict'
        ]);

        session_start();
        // Verify logout duration
        $this->timeout();
    }

    public function timeout()
    {
        $timeout = config('security.session.timeout', 1800);
        if ($timeout && time() - ($_SESSION['last_activity'] ?? time()) > $timeout) {
            session_destroy();
        }
        $_SESSION['last_activity'] = time();

        if (config('security.session.verify_user_agent', true)) {
            if (($_SESSION['user_agent'] ?? $_SERVER['HTTP_USER_AGENT']) !== $_SERVER['HTTP_USER_AGENT']) {
                session_destroy();
            }
            $_SERVER['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        }
    }

    public function regenerate()
    {
        session_regenerate_id();
    }

    public function unset()
    {
        session_unset();
        session_destroy();
        setcookie(session_name(), '', time() - 3600);
    }

    public function get($key = '', $default = null)
    {
        if (empty($key))
            return $_SESSION;
        if (!isset($_SESSION[$key]))
            return $default;
        return $_SESSION[$key];
    }

    public function remove($key)
    {
        unset($_SESSION[$key]);
    }

    public function set($key, $value)
    {
        return $_SESSION[$key] = $value;
    }
}