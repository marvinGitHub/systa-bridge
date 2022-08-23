<?php

class StringBuffer
{
    private $buffer = '';

    public function append(string $data)
    {
        $this->buffer .= $data;
    }

    public function get(): string
    {
        return $this->buffer;
    }

    public function remove(string $sequence)
    {
        $this->buffer = str_replace($sequence, '', $this->buffer);
    }
}