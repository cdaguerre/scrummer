<?php

namespace Scrummer\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Trello\Events;
use Trello\Event\CardEvent;
use Scrummer\Scrum\LabelMap;
use Scrummer\Scrummer;

class CardLabelListener extends AbstractEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            Events::CARD_ADD_LABEL    => 'onCardLabelAdd',
            Events::CARD_REMOVE_LABEL => 'onCardLabelRemove',
        );
    }

    public function onCardLabelAdd(CardEvent $event)
    {
        $card = $event->getCard();
        $data = $event->getRequestData();

        foreach ($this->scrummer->getIssuesAssociatedToCard($card) as $issue) {
            $issue->addLabels(LabelMap::trelloToGithub($data['label']['color']));
        }
    }

    public function onCardLabelRemove(CardEvent $event)
    {
        $card = $event->getCard();
        $data = $event->getRequestData();

        foreach ($this->scrummer->getIssuesAssociatedToCard($card) as $issue) {
            $issue->removeLabel(LabelMap::trelloToGithub($data['value']));
        }
    }
}
