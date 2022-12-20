<?php

class PluginHandler
{
    /**
     * @var PluginContext
     */
    private $defaultContext;
    private $plugins;

    public function __construct(PluginContext $context)
    {
        $this->setDefaultContext($context);
        $this->plugins = new SplObjectStorage();
    }

    public function setDefaultContext(PluginContext $context)
    {
        $this->defaultContext = $context;
    }

    private function getDefaultContext(): PluginContext
    {
        return $this->defaultContext;
    }

    public function register(PluginAbstract $plugin)
    {
        $this->plugins->offsetSet($plugin, true);
    }

    public function disable(PluginAbstract $plugin)
    {
        $this->plugins->offsetSet($plugin, false);
    }

    public function enable(PluginAbstract $plugin)
    {
        $this->plugins->offsetSet($plugin, true);
    }

    public function run()
    {
        try {
            /** @var PluginAbstract $plugin */
            foreach ($this->plugins as $plugin) {
                $pluginEnabled = $this->plugins[$plugin];

                if (!$pluginEnabled) {
                    continue;
                }

                $plugin->run($this->getDefaultContext());
            }
        } catch (Exception $e) {
            $this->getDefaultContext()->getLog()->print('error', $e->getMessage());
            $this->getDefaultContext()->getLog()->print('error', $e->getTraceAsString());
        }
    }
}