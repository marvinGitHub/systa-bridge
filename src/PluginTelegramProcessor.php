<?php

class PluginTelegramProcessor extends PluginAbstract
{
    private function determineTelegram(string $value): ?string
    {
        $matches = null;
        $isTelegram =
            1 === preg_match('/(fc200c01[\da-f]{62})/', $value, $matches) ||
            1 === preg_match('/(fc220c02[\da-f]{66})/', $value, $matches) ||
            1 === preg_match('/(fd170c03[\da-f]{60})/', $value, $matches) ||
            1 === preg_match('/(fd05aa0c[\da-f]{8})/', $value, $matches) ||
            1 === preg_match('/(fd140c03[\da-f]{38})/', $value, $matches) ||
            1 === preg_match('/(fd2f0c0301[\da-f]{90})/', $value, $matches) ||
            1 === preg_match('/(fd2f0c0300[\da-f]{90})/', $value, $matches) ||
            1 === preg_match(sprintf('/(%s)/', SystaBridge::COMMAND_START_MONITORING_V1), $value, $matches) ||
            1 === preg_match(sprintf('/(%s)/', SystaBridge::COMMAND_START_MONITORING_V2), $value, $matches);

        if (!$isTelegram) {
            return null;
        }

        return $matches[1];
    }

    public function run(PluginContext $context)
    {
        $telegram = $this->determineTelegram($context->getBuffer()->get());

        if (null === $telegram) {
            return;
        }

        $checksum = SystaBridge::checksum(substr($telegram, 0, strlen($telegram) - 2));
        $expected = substr($telegram, strlen($telegram) - 2);

        if ($checksum != $expected) {
            $context->getLog()->append(sprintf('Checksum mismatch. telegram: %s expected: %s computed: %s', $telegram, $expected, $checksum));
            return;
        }

        $context->getMonitor()->process($telegram);
        $context->getBuffer()->remove($telegram);
    }
}