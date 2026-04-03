<?php

namespace framework\web\request;

use stdClass;

/**
 * Request Class
 * 
 * This class provides several helper
 * methods and utilities over the raw
 * $_GET, $_POST, $_FILES, etc. superglobals.
 */
class Request
{
    protected $vals;
    protected $files;

    public function __construct()
    {
        // Initialize all files
        foreach ($_FILES as $key => $value) {
            $this->files[$key] = new UploadedFile($value);
        }
    }

    public function __get($name)
    {
        if (isset($this->vals[$name])) {
            return $this->vals[$name];
        }
    }

    public function path()
    {
        return app()->url->path();
    }

    /**
     * Get a value from the $_GET superglobal, with an optional default.
     * 
     * @param string $key The key to retrieve from the $_GET array.
     * @param mixed $default The default value to return if the key is not set.
     * @return mixed The value from $_GET if set, otherwise the default value.
     */
    public function get(string $key = '', $default = null)
    {
        if ($key === '') {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    /**
     * Get a value from the $_POST superglobal, with an optional default.
     * @param string $key The key to retrieve from the $_POST array.
     * @param mixed $default The default value to return if the key is not set.
     * @return mixed The value from $_POST if set, otherwise the default value.
     */
    public function post(string $key = '', $default = null)
    {
        if ($key === '') {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }

    /**
     * Get a value from either $_POST or $_GET, with an optional default. POST takes precedence over GET.
     * @param string $key The key to retrieve from the $_POST or $_GET arrays.
     * @param mixed $default The default value to return if the key is not set.
     * @return mixed The value from $_POST if set, otherwise the value from $_GET, or the default value.
     */
    public function input(string $key = '', $default = null)
    {
        if (empty($key)) {
            return array_merge($this->get(), $this->post());
        }
        return $this->post($key, $this->get($key, $default));
    }

    /**
     * Get the HTTP method of the request, supporting method override via a __method query parameter. Defaults to GET if not specified.
     * @return string The HTTP method of the request.
     */
    public function method()
    {
        return $_GET['__method'] ?? $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public function file(string $key = '')
    {
        if (empty($key)) {
            return $this->files;
        }
        return $this->files[$key] ?? null;
    }

    public function files(string $key = '')
    {
        if (empty($key)) {
            return $this->files;
        }
        return $this->files[$key] ?? null;
    }

    /**
     * Attach a new variable to $request instance
     * 
     * This can be used to attach auth status, flags, statuses, etc. to the request
     * object. They can later be accessed similar to a regular property.
     * 
     * Example:
     * Request::put('data', 'test');
     * $data = $request->data;
     */
    public function put($key, $value)
    {
        $this->vals[$key] = $value;
    }

    /**
     * Validates the current request
     * 
     * This function automatically redirects to the previous form
     * and errors can then be displayed using `@error` directive.
     */
    public function validate(array|string $rules)
    {
        $data = $this->input(); // or all()

        $errors = app()->validator->validate((object) $data, $rules);

        if ($errors) {
            // store errors + old input in session
            app()->session->set('errors', $errors);
            app()->session->set('old', $data);

            // redirect back
            app()->url->back();
            exit;
        }

        return true;
    }
}