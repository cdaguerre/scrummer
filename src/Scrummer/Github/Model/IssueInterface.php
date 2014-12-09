<?php

namespace Scrummer\Github\Model;

interface IssueInterface
{
    const STATE_CLOSED = 'closed';
    const STATE_OPEN   = 'open';

    /**
     * Get the issue number
     *
     * @return int
     */
    public function getId();

    /**
     * Get the url of the HTML representation of the issue
     *
     * @return string
     */
    public function getHtmlUrl();

    /**
     * Commit any changes made to issue
     *
     * @return IssueInterface
     */
    public function save();

    /**
     * Add an issue to card
     *
     * @return IssueInterface
     */
    public function addCard(CardInterface $issue);

    /**
     * Get an array of card ids linked to the issue
     *
     * @return array
     */
    public function getCardIds();

    /**
     * Check whether the issue has a given card associated to it
     *
     * @param CardInterface $card
     *
     * @return bool
     */
    public function hasCard(CardInterface $card);

    /**
     * Whether the card has any cards with it or not
     *
     * @return bool
     */
    public function hasCards();

    public function getLabels();

    public function clearLabels();

    public function addLabels($labels);

    public function removeLabel($label);

    public function setClosed($bool = true);

    public static function extractIssueIdsFromString($string);
}
