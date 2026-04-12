<?php

namespace framework\web\transformers;

use framework\models\interfaces\TypeTransformer;
use framework\web\request\UploadedFile;

class UploadedFileTransformer implements TypeTransformer
{
    /**
     * Convert database path to UploadedFile object.
     * Note: This object will only have the 'path' property populated.
     */
    public function transformFromDatabase($value)
    {
        if (empty($value)) {
            return null;
        } else if ($value instanceof UploadedFile) {
            return $value;
        }
        return new UploadedFile(['path' => $value]);
    }

    /**
     * Move the uploaded file to the /uploads directory and return its new path.
     */
    public function transformToDatabase($value)
    {
        if ($value instanceof UploadedFile) {
            $result = $value->move('/uploads');
            return $result['path'];
        }
        return $value;
    }
}
