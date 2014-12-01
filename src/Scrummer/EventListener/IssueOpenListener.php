<?php

namespace Scrummer\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Scrummer\Github\Events;
use Scrummer\Github\Event\IssueEvent;

class IssueOpenListener extends AbstractEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(Events::ISSUE_OPENED => 'onIssueOpen');
    }

    public function onIssueOpen(IssueEvent $event)
    {
        $issue = $event->getIssue();
        $cards = $this->scrummer->getCardsAssociatedToIssue($issue);

        if (!count($cards)) {
            $card = $this->scrummer
                ->createCard()
                ->moveToList(Scrummer::TRELLO_LIST_SPRINT_BACKLOG)
                ->setName($issue->getTitle())
                ->setDescription($issue->getBody())
                ->save();

            $this->scrummer->addIssuesToCard($card, array($issue));
            $this->scrummer->addCardsToIssue($issue, array($card));
        } else {
            // Else associate the issue to the cards
            foreach ($this->scrummer->getCardsAssociatedToIssue($issue) as $card) {
                if (!$this->scrummer->isIssueAssociatedToCard($issue, $card)) {
                    $this->scrummer->addIssuesToCard($card, array($issue));
                }
            }
        }
    }
}
