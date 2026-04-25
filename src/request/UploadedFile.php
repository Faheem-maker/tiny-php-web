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

    public function url()
    {
        return app()->url->to('/media/' . $this->name());
    }

    public function isValid()
    {
        return $this->data['error'] === UPLOAD_ERR_OK;
    }

    public function __get($name)
    {
        if ($name == 'tmp_name') {
            return $this->data['tmp_name'] ?? $this->data['path'];
        }
        return $this->data[$name];
    }

    public function move(string $path)
    {
        if (empty($this->data['tmp_name'])) {
            $file_name = pathinfo($this->tmp_name, PATHINFO_FILENAME) . '.' . $this->ext();
            return [
                'name' => $file_name,
                'path' => $this->original_path,
                'original' => $file_name,
            ];
        }

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