<?php

namespace Scrummer\Github\Event;

use Scrummer\Github\Model\IssueInterface;

class IssueEvent extends AbstractEvent
{
    /**
     * @var IssueInterface
     */
    protected $issue;

    /**
     * Set issue
     *
     * @param IssueInterface $issue
     */
    public function setIssue(IssueInterface $issue)
    {
        $this->issue = $issue;
    }

    /**
     * Get issue
     *
     * @return IssueInterface
     */
    public function getIssue()
    {
        return $this->issue;
    }
}
