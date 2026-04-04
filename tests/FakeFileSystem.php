<?php

namespace framework\web\tests;

use framework\Component;
use framework\contracts\components\FileSystem;

class FakeFileSystem extends Component implements FileSystem
{
    public array $moved = [];

    public function exists($path): bool
    {
        return file_exists($path);
    }

    public function move($source, $destination): bool
    {
        $this->moved[] = [$source, $destination];
        return true;
    }
}