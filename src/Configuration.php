<?php

class Configuration
{
    private $pathname;
    private $pathnameDefault;

    public function __construct(string $pathname, ?string $pathnameDefault = null)
    {
        $this->pathname = $pathname;
        $this->pathnameDefault = $pathnameDefault;
    }

    public function load()
    {
        if (!file_exists($this->pathname)) {
            $this->restore();
        }
        if (false !== $configuration = file_get_contents($this->pathname)) {
            return json_decode($configuration, true);
        }
        return false;
    }

    public function restore()
    {
        return $this->save(file_get_contents($this->pathnameDefault));
    }

    /**
     * @throws Exception
     */
    public function save($configuration)
    {
        if (is_string($configuration)) {
            $configuration = json_decode($configuration, true);
        }
        if (!is_array($configuration)) {
            throw new Exception('Unsupported configuration provided');
        }
        return file_put_contents($this->pathname, json_encode($configuration, JSON_PRETTY_PRINT));
    }
}