<?php

namespace framework\web\response;

/**
 * This class serves as the base class of our Http Response.
 * It has raw methods for setting headers, writing
 * content and setting cookies, as well as others.
 * 
 * This shouldn't be used directly, instead, functions like
 * "view" and "json" will utilize it.
 */
abstract class HttpResponse {
    public abstract function render();


    /**
     * Helper Functions
     */
    public function write($content) {
        echo $content;
    }
}