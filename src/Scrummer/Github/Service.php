<?php

namespace Scrummer\Github;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Scrummer\Github\Model\Issue;
use Github\Client;

class Service
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * Constructor.
     *
     * @param Client                        $client
     * @param EventDispatcherInterface|null $dispatcher
     */
    public function __construct(Client $client, EventDispatcherInterface $dispatcher = null)
    {
        $this->client = $client;
        $this->dispatcher = $dispatcher ?: new EventDispatcher();
    }

    /**
     * Get event dispatcher
     *
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * Attach an event listener
     *
     * @param string   $eventName @see Events for name constants
     * @param callable $listener  The listener
     * @param int      $priority  The higher this value, the earlier an event
     *                            listener will be triggered in the chain (defaults to 0)
     */
    public function addListener($eventName, $listener, $priority = 0)
    {
        $this->dispatcher->addListener($eventName, $listener, $priority);
    }

    /**
     * Attach an event subscriber
     *
     * @param EventSubscriberInterface $subscriber The subscriber
     */
    public function addEventSubscriber(EventSubscriberInterface $subscriber)
    {
        $this->dispatcher->addSubscriber($subscriber);
    }

    /**
     * Checks whether a given request is a Trello webhook
     *
     * @param Request $request
     *
     * @return bool
     */
    public function isGithubWebhook(Request $request)
    {
        if (!$request->getMethod() === 'POST') {
            return false;
        }

        if (!$request->headers->has('X-GitHub-Event')) {
            return false;
        }

        return true;
    }

    /**
     * Checks whether a given request is a Trello webhook
     * and raises appropriate events @see Events
     *
     * @param Request|null $request
     */
    public function handleWebhook(Request $request = null)
    {
        if (!$request) {
            $request = Request::createFromGlobals();
        }

        if (!$this->isGithubWebhook($request)) {
            return;
        }

        if (!$action = $request->get('action', false)) {
            throw new InvalidArgumentException('Unable to determine action from request.');
        }

        $eventName = $request->headers->get('X-GitHub-Event').'_'.$action;

        switch ($eventName) {
            case Events::ISSUE_OPENED:
            case Events::ISSUE_CLOSED:
            case Events::ISSUE_REOPENED:
            case Events::ISSUE_LABELED:
            case Events::ISSUE_UNLABELED:
                $event = new Event\IssueEvent();
                $event->setIssue(new Issue($this->client, $request->get('issue')));
                $event->setRequestData($request->attributes->all());
                $this->dispatcher->dispatch($eventName, $event);
                break;
        }
    }
}
