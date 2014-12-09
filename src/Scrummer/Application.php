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
            $this['logger']->addDebug('in before hook');
            if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
                $this['logger']->addDebug('has json header');
                $data = json_decode($request->getContent(), true);
                $request->request->replace(is_array($data) ? $data : array());
            }
        });

        if ($this['config']['debug']) {
            $this['debug'] = true;
        }

        $github = array(
            'user'         => $this['config']['github.user'],
            'password'     => $this['config']['github.password'],
            'organization' => $this['config']['github.organization'],
            'repository'   => $this['config']['github.repository'],
        );

        $trello = array(
            'api_key'      => $this['config']['trello.api_key'],
            'secret'       => $this['config']['trello.secret'],
            'token'        => $this['config']['trello.token'],
            'board_id'     => $this['config']['trello.board_id'],
        );

        $this['scrummer'] = new Scrummer($trello, $github);
    }
}
