<?php

class PluginMQTTPublisher extends PluginAbstract
{
    private $broker;

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

    private function getBroker(): string
    {
        return $this->broker;
    }

    public function run(PluginContext $context)
    {
        try {
            $broker = $this->getBroker();

            $mqtt = new \PhpMqtt\Client\MqttClient($broker['host'], $broker['port'], $broker['user']);

            $mqtt->connect();

            foreach ($context->getMonitor()->load() as $key => $value) {
                $mqtt->publish(sprintf('%s/%s', $broker['path'], $key), (string)$value, 0);
            }

            $mqtt->disconnect();
        } catch (Exception $e) {
            $context->getLog()->append($e->getMessage());
            $context->getLog()->append($e->getTraceAsString());
        }
    }
}