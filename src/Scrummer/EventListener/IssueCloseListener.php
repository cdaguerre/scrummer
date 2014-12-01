<?php

namespace Scrummer\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Scrummer\Github\Events;
use Scrummer\Github\Event\IssueEvent;

class IssueCloseListener extends AbstractEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(Events::ISSUE_CLOSED => 'onIssueClose');
    }

    public function onIssueClose(IssueEvent $event)
    {
        $issue = $event->getIssue();

        foreach ($this->scrummer->getCardsAssociatedToIssue($issue) as $card) {
            // Close issue state on card
            $this->scrummer->synchronizeIssueCompletionWithCard($issue, $card);

            // Move card to 'to be staged' if there is only one issue
            // associated to the card or all associated issues are closed
            $complete = true;
            $associatedIssues = $this->scrummer->getIssuesAssociatedToCard($card);

            if (count($associatedIssues) !== 1) {
                foreach ($associatedIssues as $associatedIssue) {
                    if (!$associatedIssue->isClosed()) {
                        $complete = false;
                    }
                }
            }

            if ($complete) {
                $card->moveToList(Scrummer::TRELLO_LIST_TO_BE_STAGED)->save();
            }
        }
    }
}
