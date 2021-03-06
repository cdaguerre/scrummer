<?php

namespace Scrummer;

use Silex\Application as BaseApplication;
use Silex\Application\MonologTrait;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Scrummer\Yaml\YamlConfigServiceProvider;
use Symfony\Component\HttpKernel\Debug\ErrorHandler;
use Symfony\Component\HttpKernel\Debug\ExceptionHandler;

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

            ini_set('date.timezone', 'Europe/Paris');
            ini_set('display_errors', 1);
            error_reporting(-1);
            ErrorHandler::register();

            if ('cli' !== php_sapi_name()) {
                ExceptionHandler::register();
            }
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
