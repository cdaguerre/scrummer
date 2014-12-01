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

        if (is_array($config)) {
            $config = $this->replaceEnvVars($config);
            $this->importSearch($config, $app);

            if (isset($app['config']) && is_array($app['config'])) {
                $app['config'] = array_replace_recursive($app['config'], $config);
            } else {
                $app['config'] = $config;
            }
        }
    }

    /**
     * Looks for import directives..
     *
     * @param array $config The result of Yaml::parse().
     */
    public function importSearch(&$config, $app)
    {
        foreach ($config as $key => $value) {
            if ($key == 'imports') {
                foreach ($value as $resource) {
                    $base_dir = str_replace(basename($this->file), '', $this->file);
                    $new_config = new YamlConfigServiceProvider($base_dir.$resource['resource']);
                    $new_config->register($app);
                }
                unset($config['imports']);
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
    private function replaceEnvVars($config)
    {
        if (!is_array($config)) {
            if (preg_match('/%([^%.]+)%/', $config, $matches)) {
                return getenv($matches[1]);
            }
        } else {
            foreach ($config as $key => $value) {
                if (!is_array($value) && preg_match('/%([^%.]+)%/', $value, $matches)) {
                    $config[$key] = getenv($matches[1]);
                } else {
                    $config[$key] = $this->replaceEnvVars($value);
                }
            }
        }

        return $config;
    }
}
