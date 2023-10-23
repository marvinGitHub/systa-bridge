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

            if (!Helper::checkPortAccessibility($host = $broker['host'], $port = (int)$broker['port'], 2)) {
                throw new RuntimeException(sprintf('Unable to connect to mqtt broker: %s:%u', $host, $port));
            }

            $mqtt = new MessageQueuingTelemetryTransport($host, $port, $broker['user']);

            $connected = $mqtt->connect(true, null, $broker['user'], $broker['pass']);

            if (!$connected) {
                throw new RuntimeException(sprintf('Unable to connect to mqtt broker: %s:%u', $host, $port));
            }

            foreach ($context->getMonitor()->load() as $key => $value) {
                $topic = sprintf('%s/%s', ltrim($broker['path'], '/'), $key);
                $published = $mqtt->publish($topic, json_encode(['value' => $value]));

                if (!$published) {
                    throw new RuntimeException(sprintf('Unable to publish topic: %s', $topic));
                }
            }

            $mqtt->close();
        } catch (Exception $e) {
            $context->getLog()->print('error', sprintf('%s: failed publishing data', static::class));
            $context->getLog()->print('error', $e->getMessage());
            $context->getLog()->print('error', $e->getTraceAsString());
        } finally {
            $mqtt->close();
        }
    }
}