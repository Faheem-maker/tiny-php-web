<?php

namespace framework\web\request;

/**
 * Represents a file uploaded to the system
 * 
 * @property string $name
 * @property string $type
 * @property int $size
 * @property string $tmp_name
 * @property int $error
 */
class UploadedFile
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function ext()
    {
        $name = $this->data['name'];
        return pathinfo($name, PATHINFO_EXTENSION);
    }

    public function isValid()
    {
        return $this->data['error'] === UPLOAD_ERR_OK;
    }

    public function __get($name)
    {
        return $this->data[$name];
    }
}