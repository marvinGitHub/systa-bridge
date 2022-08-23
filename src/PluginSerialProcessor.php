<?php

class PluginSerialProcessor extends PluginAbstract
{
    public function run(PluginContext $context)
    {
        $data = $context->getSerial()->readPort();

        for ($i = 0; $i < strlen($data); $i++) {

            $char = ord($data{$i});

            $hex = Helper::getFixed(dechex($char));

            $context->getBuffer()->append($hex);

            $context->getDump()->write($hex);
        }

        $context->getDump()->write(PHP_EOL);
    }
}