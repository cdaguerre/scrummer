<?php

namespace Scrummer\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Trello\Events;
use Trello\Event\CardEvent;

class CardCreateListener extends AbstractEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(Events::CARD_CREATE => 'onCardCreate');
    }

    public function onCardCreate(CardEvent $event)
    {
        echo 'happend';
        die;

        $card = $event->getCard();

        $issue = $this->scrummer
            ->createIssue()
            ->setTitle($card->getName())
            ->setBody($card->getDescription())
            ->save();

        $this->scrummer->addIssuesToCard($card, array($issue));
    }
}
