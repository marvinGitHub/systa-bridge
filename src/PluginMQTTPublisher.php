<?php

class PluginMQTTPublisher extends PluginAbstract
{
    private $broker;
    private $checksum;

    public function __construct(string $broker)
    {
        $this->setBroker($broker);
    }

    public function setBroker(string $broker)
    {
        if (false === $fragments = parse_url($broker)) {
            throw new InvalidArgumentException(sprintf('Unsupported broker url detected: %s', var_export($broker, true)));
        }
        $this->broker = $fragments;
    }

    private function getBroker(): array
    {
        return $this->broker;
    }

    public function run(PluginContext $context)
    {
        $message = $this->createMessage($context->getMonitor()->load());

        if ($this->checksum === $checksum = md5($message)) {
            $context->getLog()->append(sprintf('%s: skip', static::class));
            return;
        }

        $this->checksum = $checksum;

        try {
            $broker = $this->getBroker();

            $context->getLog()->append(sprintf('%s: publish data with configuration: %s', static::class, var_export($broker, true)));

            $mqtt = new Bluerhinos\phpMQTT($broker['host'], $broker['port'], $broker['user']);

            $mqtt->connect(true, null, $broker['user'], $broker['pass']);
            $mqtt->publish(ltrim($broker['path'], '/'), $message);
            $mqtt->close();

            $context->getLog()->append(sprintf('%s: successfully published data', static::class));
        } catch (Exception $e) {
            $context->getLog()->append($e->getMessage());
            $context->getLog()->append($e->getTraceAsString());
        }
    }

    private function createMessage(array $data)
    {
        unset($data['timestamp']);
        return json_encode($data);
    }
}