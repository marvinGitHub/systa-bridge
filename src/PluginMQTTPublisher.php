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

    private function getBroker(): array
    {
        return $this->broker;
    }

    public function run(PluginContext $context)
    {
        try {
            $broker = $this->getBroker();

            $mqtt = new Bluerhinos\phpMQTT($broker['host'], $broker['port'], $broker['user']);

            $connected = $mqtt->connect(true, null, $broker['user'], $broker['pass']);

            if (!$connected) {
                throw new RuntimeException(sprintf('Unable to connect to mqtt broker: %s', $broker['host']));
            }

            foreach ($context->getMonitor()->load() as $key => $value) {
                $mqtt->publish(sprintf('%s/%s', ltrim($broker['path'], '/'), $key), json_encode(['value' => $value]));
            }

            $mqtt->close();
        } catch (Exception $e) {
            $context->getLog()->append(sprintf('%s: failed publishing data', static::class));
            $context->getLog()->append($e->getMessage());
            $context->getLog()->append($e->getTraceAsString());
        }
    }
}