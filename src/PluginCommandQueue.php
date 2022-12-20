<?php

class PluginCommandQueue extends PluginAbstract
{
    public function run(PluginContext $context)
    {
        if ($command = $context->getQueue()->next()) {
            $context->getSerial()->sendMessage(hex2bin($command));
            $context->getLog()->print('info', sprintf('Command %s sent to device', $command));
            $context->getDump()->write($command);
            $context->getDump()->write(PHP_EOL);
        }
    }
}