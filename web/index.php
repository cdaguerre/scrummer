<?php

require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Debug\ErrorHandler;
use Symfony\Component\HttpKernel\Debug\ExceptionHandler;
use Trello\Service as TrelloService;
use Scrummer\Github\Service as GithubService;
use Scrummer\Scrummer;
use Scrummer\Application;
use Scrummer\EventListener\CardCreateListener;
use Scrummer\EventListener\CardUpdateListener;
use Scrummer\EventListener\IssueOpenListener;
use Scrummer\EventListener\IssueReopenListener;
use Scrummer\EventListener\IssueCloseListener;
use Scrummer\EventListener\IssueLabelListener;

ini_set('date.timezone', 'Europe/Paris');
ini_set('display_errors', 1);
error_reporting(-1);
ErrorHandler::register();
if ('cli' !== php_sapi_name()) {
    ExceptionHandler::register();
}

Request::enableHttpMethodParameterOverride();

$app = new Application();

$app->before(function (Request $request) {
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());
    }
});

$app->get('/trello/webhooks', function (Request $request) use ($app) {
    $manager = $app['scrummer']->getTrelloManager();

    $token = $manager->getToken($app['config']['trello.token']);

    $webhooks = $token->getWebhooks();

    $watched = array();

    foreach ($webhooks as $webhook) {
        $watched[$webhook->getModelId()][] = $webhook;
    }

    return $app['twig']->render('Webhook/index.twig', array(
        'webhooks' => $webhooks,
        'map'      => $watched,
        'boards'   => $token->getMember()->getBoards(),
    ));
});

$app->delete('/trello/webhooks/{id}', function ($id) use ($app) {
    $manager = $app['scrummer']->getTrelloManager();

    $webhook = $manager->getWebhook($id);
    $webhook->remove();

    return $app->redirect('/trello/webhooks');
});

$app->post('/trello', function (Request $request) use ($app) {
    $app['logger']->addDebug($request->getContent());

    if (!$request->request->get('action', false)) {
        return 'NA';
    }

    $client = $app['scrummer']->getTrelloClient();
    $service = new TrelloService($client, $app['dispatcher']);

    if (!$request->getMethod() === 'POST') {
        $app['logger']->addDebug('not post');
    }

    if (!$request->headers->has('X-Trello-Webhook')) {
        $app['logger']->addDebug('no header');
    }

    $service->addEventSubscriber(new CardUpdateListener($app['scrummer']));
    $service->addEventSubscriber(new CardCreateListener($app['scrummer']));

    $service->handleWebhook($request);

    return 'OK';
});

$app->post('/github', function (Request $request) use ($app) {

    $client = $app['scrummer']->getGithubClient();
    $service = new GithubService($client, $app['dispatcher']);

    $service->addEventSubscriber(new IssueOpenListener($app['scrummer']));
    $service->addEventSubscriber(new IssueCloseListener($app['scrummer']));
    $service->addEventSubscriber(new IssueReopenListener($app['scrummer']));
    $service->addEventSubscriber(new IssueLabelListener($app['scrummer']));

    $service->handleWebhook($request);

    return 'OK';
});

$app->get('/deployment', function (Request $request) use ($app) {

    $client     = $app['scrummer']->getTrelloClient();
    $board      = $app['scrummer']->getBoard();

    $toBeStaged = $board->getList(Scrummer::TRELLO_LIST_TO_BE_STAGED);
    $staged     = $board->getList(Scrummer::TRELLO_LIST_STAGED);

    $client->lists()->cards()->moveAll($toBeStaged->getId(), $board->getId(), $staged->getId());

    return 'OK';
});

$app->get('/trello', function (Request $request) use ($app) {
    return 'Trello set up.';
});

$app->run();
