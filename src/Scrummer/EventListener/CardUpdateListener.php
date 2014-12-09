<?php

namespace Scrummer\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Trello\Events;
use Trello\Event\CardEvent;
use Trello\Event\CardChecklistEvent;
use Scrummer\Scrum\LabelMap;
use Scrummer\Scrummer;

class CardUpdateListener extends AbstractEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            Events::CARD_UPDATE => 'onCardUpdate',
            Events::CARD_ADD_LABEL => 'onCardLabelAdd',
            Events::CARD_REMOVE_LABEL => 'onCardLabelRemove',
            Events::CARD_UPDATE_CHECKLIST_ITEM_STATE => 'onCheckItemStateChange',
        );
    }

    public function onCardUpdate(CardEvent $event)
    {
        $card = $event->getCard();

        $issues = $this->scrummer->getIssuesAssociatedToCard($card);

        // Sync completion
        foreach ($issues as $issue) {
            if ($card->isClosed() !== $issue->isComplete()) {
                $issue->setComplete($card->isClosed())->save();
            }
        }

        // Update issue name and description only if
        // there is only one issue associated
        if (count($issues) === 1) {
            $issue = reset($issues);
            $issue
                ->setTitle($card->getName())
                ->setBody($card->getDescription())
                ->save();
        }
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

    public function onCheckItemStateChange(CardChecklistEvent $event)
    {
        $data = $event->getRequestData();
        $card = $event->getCard();
        $checklist = $event->getChecklist();

        $issueIds = $this->scrummer->extractIssueIdsFromString($data['checkItem']['name']);

        if (!count($issueIds) || $checklist->getName() !== Scrummer::GITHUB_CHECKLIST_NAME) {
            return;
        }

        $issueId = reset($issueIds);
        $complete = in_array($data['checkItem']['state'], array('complete', true));

        // Update the issue state on Github
        $issue = $this->scrummer->getIssue($issueId)
            ->setComplete($complete)
            ->save();

        // If the item was set as complete, check the state of
        // all issues associated to the card and move it accordingly
        if ($complete) {
            foreach ($this->scrummer->getIssuesAssociatedToCard($card) as $issue) {
                if (!$issue->isComplete()) {
                    $complete = false;
                }
            }
        }

        if ($complete) {
            $card->moveToList(Scrummer::TRELLO_LIST_TO_BE_STAGED);
        }

        $card->save();
    }
}
