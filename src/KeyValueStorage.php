<?php

class KeyValueStorage
{
    private $path;

    public function __construct(string $path)
    {
        $this->setPath($path);
    }

    public function setPath(string $path)
    {
        if (!is_dir($path)) {
            throw new InvalidArgumentException(sprintf('Unknown path provided: %s', $path));
        }

        $this->path = $path;
    }

    private function resolvePath(string $key): string
    {
        return sprintf('%s/%s', $this->path, md5($key));
    }

    public function set(string $key, $value)
    {
        return file_put_contents($this->resolvePath($key), serialize($value));
    }

    public function get(string $key)
    {
        if (!file_exists($path = $this->resolvePath($key))) {
            return null;
        }

        if (!$data = file_get_contents($path)) {
            return null;
        }

        return unserialize($data);
    }
}