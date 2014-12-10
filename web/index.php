<?php

require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Trello\Service as TrelloService;
use Scrummer\Scrummer;
use Scrummer\Application;
use Scrummer\Github\Service as GithubService;
use Scrummer\EventListener\CardCreateListener;
use Scrummer\EventListener\CardUpdateListener;
use Scrummer\EventListener\CardLabelListener;
use Scrummer\EventListener\IssueOpenListener;
use Scrummer\EventListener\IssueReopenListener;
use Scrummer\EventListener\IssueCloseListener;
use Scrummer\EventListener\IssueLabelListener;

Request::enableHttpMethodParameterOverride();

$app = new Application();

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

$app->get('/endpoint', function (Request $request) use ($app) {

    return 'OK';
});

$app->post('/endpoint', function (Request $request) use ($app) {

    $app['logger']->addDebug($request->getContent());

    $scrummer   = $app['scrummer'];
    $dispatcher = $app['dispatcher'];

    $githubClient = $scrummer->getGithubClient();
    $trelloClient = $scrummer->getTrelloClient();

    $githubService = new GithubService($githubClient, $dispatcher);
    $trelloService = new TrelloService($trelloClient, $dispatcher);

    // Trello events
    $dispatcher->addEventSubscriber(new CardUpdateListener($scrummer));
    $dispatcher->addEventSubscriber(new CardCreateListener($scrummer));
    $dispatcher->addEventSubscriber(new CardLabelListener($scrummer));

    // Github events
    $dispatcher->addEventSubscriber(new IssueOpenListener($scrummer));
    $dispatcher->addEventSubscriber(new IssueCloseListener($scrummer));
    $dispatcher->addEventSubscriber(new IssueReopenListener($scrummer));
    $dispatcher->addEventSubscriber(new IssueLabelListener($scrummer));

    $githubService->handleWebhook($request);
    $trelloService->handleWebhook($request);

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

$app->run();
