<?php

namespace framework\web\components;

use ErrorException;
use framework\contracts\HttpException;
use framework\Component;

class ErrorHandler extends Component
{

    public function init(): void
    {
        set_error_handler(function ($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) {
                // Respect the @ error suppression operator
                return;
            }
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(function ($ex) {
            if ($ex instanceof HttpException) {
                http_response_code($ex->status);
                $path = 'errors.' . $ex->status;
                if (view($path)->exists()) {
                    echo view($path, compact('ex'))->render();
                    return;
                }
            }

            if (env('ENVIRONMENT', 'PRODUCTION') == 'DEBUG') {
                echo view('errors.500_dev', compact('ex'))->render();
            } else {
                echo view('errors.500_dev', compact('ex'))->render();
            }
        });
    }
}