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
        $this->handleLabelChange($event, 'add');
    }

    public function onCardLabelRemove(CardEvent $event)
    {
        $this->handleLabelChange($event, 'remove');
    }

    private function handleLabelChange(CardEvent $event, $action)
    {
        $card = $event->getCard();
        $data = $event->getRequestData();

        foreach ($this->scrummer->getIssuesAssociatedToCard($card) as $issue) {
            if ($action === 'add') {
                $issue->addLabels(LabelMap::trelloToGithub($data['label']['color']));
            } elseif ($action === 'remove') {
                $issue->removeLabel(LabelMap::trelloToGithub($data['value']));
            }
        }
    }
}
