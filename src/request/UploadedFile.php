<?php

namespace framework\web\request;

use Exception;

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
        $name = $this->data['name'] ?? $this->data['path'];
        return pathinfo($name, PATHINFO_EXTENSION);
    }

    public function name()
    {
        return $this->data['name'] ?? (pathinfo($this->data['path'], PATHINFO_FILENAME) . '.' . $this->ext());
    }

    public function isValid()
    {
        return $this->data['error'] === UPLOAD_ERR_OK;
    }

    public function __get($name)
    {
        return $this->data[$name];
    }

    public function move(string $path)
    {
        $path = app()->path->resolveWithDefault($path, '@storage');
        $file_name = pathinfo($this->tmp_name, PATHINFO_FILENAME) . '.' . $this->ext();
        $target_path = $path . DIRECTORY_SEPARATOR . $file_name;

        app()->fs->move($this->tmp_name, $target_path);

        return [
            'name' => $file_name,
            'path' => $target_path,
            'original' => $this->data['name'],
        ];
    }
}