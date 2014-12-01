<?php

namespace Scrummer\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Scrummer\Github\Events;
use Scrummer\Github\Event\IssueEvent;

class IssueReopenListener extends AbstractEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(Events::ISSUE_REOPENED => 'onIssueReopen');
    }

    public function onIssueReopen(IssueEvent $event)
    {
        $issue = $event->getIssue();

        foreach ($this->scrummer->getCardsAssociatedToIssue($issue) as $card) {
            // Just in the issue was not associated to the card
            if (!$this->scrummer->isCardAssociatedToIssue($card, $issue)) {
                $this->scrummer->addIssuesToCard($card, array($issue));
            }

            // Reopen issue state on card
            $this->scrummer->synchronizeIssueCompletionWithCard($issue, $card);

            // Card were probably in the 'done' or 'to be staged' columns,
            // just move them to 'doing'
            $card->moveToList(Scrummer::TRELLO_LIST_DOING)->save();
        }
    }
}
