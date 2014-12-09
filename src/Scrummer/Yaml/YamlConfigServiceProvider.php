<?php

namespace Scrummer\Yaml;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Yaml\Yaml;

class YamlConfigServiceProvider implements ServiceProviderInterface
{
    /**
     * @var string
     */
    protected $file;

    /**
     * Constructor.
     *
     * @param string $file Config file name
     */
    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $config = Yaml::parse(file_get_contents($this->file));
        $config = $config['parameters'];

        if (is_array($config)) {
            $config = $this->parseParameters($config);

            if (isset($app['config']) && is_array($app['config'])) {
                $app['config'] = array_replace_recursive($app['config'], $config);
            } else {
                $app['config'] = $config;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }

    /**
     * Replace any values like %value% by the result of getenv(value)
     */
    private function parseParameters($config)
    {
        if (!is_array($config)) {
            if (preg_match('/%([^%.]+)%/', $config, $matches)
                && $env = getenv($matches[1])) {
                return $env;
            }
        } else {
            foreach ($config as $key => $value) {
                $config[$key] = $this->parseParameters($value);
            }
        }

        return $config;
    }
}
