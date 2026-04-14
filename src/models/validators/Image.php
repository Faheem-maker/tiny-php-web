<?php

namespace framework\web\models\validators;

use Attribute;
use framework\contracts\Validator;
use framework\web\request\UploadedFile;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class Image extends Validator
{
    public $message = 'The uploaded file must be a valid image.';

    /**
     * Validates if the given value is a valid image file.
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

        $tmpPath = $value->tmp_name;

        // Ensure the temporary file exists
        if (empty($tmpPath) || !file_exists($tmpPath)) {
            return false;
        }

        // Use getimagesize to verify it's a valid image
        // It returns false if the file is not an image
        $imageInfo = @getimagesize($tmpPath);

        return $imageInfo !== false;
    }
}
