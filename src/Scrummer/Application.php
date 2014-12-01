<?php

namespace Scrummer;

use Silex\Application as BaseApplication;
use Silex\Application\MonologTrait;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Scrummer\Yaml\YamlConfigServiceProvider;

class Application extends BaseApplication
{
    use MonologTrait;

    public function __construct(array $values = array())
    {
        parent::__construct($values);

        $this->register(new YamlConfigServiceProvider(__DIR__.'/../../config.yml'));
        $this->register(new MonologServiceProvider(), array(
            'monolog.logfile' => __DIR__.'/../../web/development.log',
        ));
        $this->register(new TwigServiceProvider(), array(
            'twig.path' => __DIR__.'/Views',
        ));

        $this->before(function (Request $request) {
            if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
                $data = json_decode($request->getContent(), true);
                $request->request->replace(is_array($data) ? $data : array());
            }
        });

        if ($this['config']['debug']) {
            $this['debug'] = true;
        }

        $this['scrummer'] = new Scrummer($this['config']['trello'], $this['config']['github']);
    }
}
