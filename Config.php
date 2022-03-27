<?php

namespace Msgframework\Lib\Config;

use Msgframework\Lib\Registry\Registry;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;

class Config extends Registry
{
    private ConfigurationInterface $configuration;
    protected string $cache_dir;

    public function __construct(ConfigurationInterface $configuration, string $cache_dir)
    {
        parent::__construct();
        $this->configuration = $configuration;
        $this->cache_dir = $cache_dir;
    }

    public function load(string $name, string $key, array $directories = array()) : void
    {
        $cachePath = $this->cache_dir . '/config/'.$key.'.php';
        $configFile = $name.'.yml';

        $cache = new ConfigCache($cachePath, true);

        if (!$cache->isFresh()) {
            $locator = new FileLocator($directories);

            $loader = new YamlConfigLoader($locator);
            $configFilePath = $locator->locate($configFile);
            $configValues = $loader->load($configFilePath);
            $resource = new FileResource($configFilePath);

            $processor = new Processor();

            $processedConfiguration = $processor->processConfiguration(
                $this->configuration,
                $configValues
            );

            $cache->write(serialize($processedConfiguration), array($resource));
        }

        $vars = unserialize(file_get_contents($cache->getPath()));

        $this->merge(new Registry($vars));
    }

    public function has(string $key) : bool
    {
        return $this->exists($key);
    }
}