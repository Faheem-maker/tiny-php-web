<?php

namespace framework\web\request;

use framework\web\response\ViewResponse;

class Response {
    /**
     * Send a plain text response
     * @param string $content
     */
    public function send($content) {
        echo $content;
    }

    /**
     * Send a JSON response
     * @param mixed $data
     */
    public function json($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    /**
     * Redirect to a different URL
     * @param string $url
     */
    public function redirect($url) {
        $url = app()->url->to($url);
        header("Location: $url");
        exit;
    }

    /**
     * Render a view template
     * @param string $template
     * @param array $data
     * @return ViewResponse
     */
    public function view($template, $data = []) {
        return new ViewResponse($template, $data);
    }
}