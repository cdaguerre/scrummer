<?php

namespace Scrummer\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Scrummer\Github\Events;
use Scrummer\Github\Event\IssueEvent;
use Scrummer\Scrum\LabelMap;

class IssueLabelListener extends AbstractEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            Events::ISSUE_LABELED   => 'onIssueLabel',
            Events::ISSUE_UNLABELED => 'onIssueUnlabel',
        );
    }

    public function onIssueLabel(IssueEvent $event)
    {
        $issue = $event->getIssue();
        $data  = $event->getRequestData();
        $label = $data['label'];

        foreach ($this->scrummer->getCardsAssociatedToIssue($issue) as $card) {
            $card->addLabel(LabelMap::githubToTrello($label['name']));
        }
    }

    public function onIssueUnlabel(IssueEvent $event)
    {
        $issue = $event->getIssue();
        $data  = $event->getRequestData();
        $label = $data['label'];

        foreach ($this->scrummer->getCardsAssociatedToIssue($issue) as $card) {
            $card->removeLabel(LabelMap::githubToTrello($label['name']));
        }
    }
}
