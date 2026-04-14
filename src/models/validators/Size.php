<?php

namespace framework\web\models\validators;

use Attribute;
use framework\contracts\Validator;
use framework\web\request\UploadedFile;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class Size extends Validator
{
    public $message = 'The file size must be at most %s KB.';
    public int $size;

    /**
     * @param int $size Maximum allowed size in Kilobytes (KB)
     */
    public function __construct(int $size)
    {
        $this->size = $size;
    }

    /**
     * Validates if the given UploadedFile size is within the allowed limit.
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

        // Convert the max allowed size from KB to bytes
        $maxBytes = $this->size * 1024;

        // UploadedFile->size returns the size in bytes
        return $value->size <= $maxBytes;
    }
}
