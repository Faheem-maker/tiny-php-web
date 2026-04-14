<?php

namespace framework\web\models\validators;

use Attribute;
use framework\contracts\Validator;
use framework\web\request\UploadedFile;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class Extension extends Validator
{
    public $message = 'The file extension is not allowed.';

    /**
     * @var array List of allowed extensions
     */
    public array $allowed = [];

    /**
     * @var array List of disallowed extensions
     */
    public array $disallowed = [];

    /**
     * @param array $allowed List of allowed extensions (e.g., ['jpg', 'png'])
     * @param array $disallowed List of disallowed extensions (e.g., ['exe', 'php'])
     */
    public function __construct(array $allowed = [], array $disallowed = [])
    {
        $this->allowed = $allowed;
        $this->disallowed = $disallowed;
    }

    /**
     * Validates if the given UploadedFile has an allowed extension and not a disallowed one.
     * 
     * @param mixed $value The value to validate (expected to be an instance of UploadedFile)
     * @param mixed $document The entire document context (unused)
     * @return bool True if valid, false otherwise
     */
    public function validate($value, $document = null): bool
    {
        if (!($value instanceof UploadedFile)) {
            return false;
        }

        // Check if the upload was successful
        if (!$value->isValid()) {
            return false;
        }

        $extension = strtolower($value->ext());

        // Handle case-insensitive comparison and check allowed list
        if (!empty($this->allowed)) {
            $allowed = array_map('strtolower', $this->allowed);
            if (!in_array($extension, $allowed)) {
                return false;
            }
        }

        // Handle case-insensitive comparison and check disallowed list
        if (!empty($this->disallowed)) {
            $disallowed = array_map('strtolower', $this->disallowed);
            if (in_array($extension, $disallowed)) {
                return false;
            }
        }

        return true;
    }
}
