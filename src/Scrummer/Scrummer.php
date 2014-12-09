<?php

namespace Scrummer;

use Trello\Client as TrelloClient;
use Github\Client as GithubClient;
use Trello\Model\Card;
use Trello\Model\CardInterface;
use Trello\Manager;
use Scrummer\Github\Model\Issue;

class Scrummer
{
    const GITHUB_CHECKLIST_NAME      = 'Github';

    const TRELLO_LIST_SPRINT_BACKLOG = 'SPRINT BACKLOG';
    const TRELLO_LIST_DOING          = 'DOING';
    const TRELLO_LIST_TO_BE_STAGED   = 'TO BE STAGED';
    const TRELLO_LIST_STAGED         = 'STAGE';

    const TRELLO_LINK_REGEX          = "/https:\/\/trello.com\/c\/(\w+)/";

    /**
     * @var TrelloClient
     */
    protected $trello;

    /**
     * @var GithubClient
     */
    protected $github;

    /**
     * @var Manager
     */
    protected $trelloManager;

    /**
     * Github organization name
     *
     * @var string
     */
    protected $organization;

    /**
     * Github repository name
     *
     * @var string
     */
    protected $repository;

    /**
     * Trello board id
     *
     * @var string
     */
    protected $boardId;

    /**
     * Constructor.
     *
     * @param array $tConf Trello config array
     * @param array $gConf Github config array
     */
    public function __construct(array $tConf, array $gConf)
    {
        $this->trello = new TrelloClient();
        $this->trello->authenticate($tConf['api_key'], $tConf['token'], TrelloClient::AUTH_URL_CLIENT_ID);

        $this->github = new GithubClient();
        $this->github->authenticate($gConf['user'], $gConf['password']);

        $this->trelloManager = new Manager($this->trello);

        $this->organization = $gConf['organization'];
        $this->repository   = $gConf['repository'];
        $this->boardId      = $tConf['board_id'];
    }

    public function getTrelloClient()
    {
        return $this->trello;
    }

    public function getGithubClient()
    {
        return $this->github;
    }

    public function getTrelloManager()
    {
        return $this->trelloManager;
    }

    public function getBoard()
    {
        return $this->trelloManager->getBoard($this->boardId);
    }

    /**
     * Get issue
     *
     * @param string|array $issueIdOrData The issue's number or issue data from event
     *
     * @return IssueInterface
     */
    public function getIssue($issueIdOrData)
    {
        if (is_array($issueIdOrData)) {
            return new Issue($this->github, $issueIdOrData);
        }

        $issueData = $this->github->api('issue')->show(
            $this->organization,
            $this->repository,
            $issueIdOrData
        );

        return new Issue($this->github, $issueData);
    }

    /**
     * Create an issue
     *
     * @return IssueInterface
     */
    public function createIssue()
    {
        return new Issue($this->github, array(
            'organization' => $this->organization,
            'repository' => $this->repository,
        ));
    }

    /**
     * Get card
     *
     * @param string $cardId The card's identifier
     *
     * @return CardInterface
     */
    public function getCard($cardId)
    {
        return $this->trelloManager->getCard($cardId);
    }

    /**
     * Create card
     *
     * @return CardInterface
     */
    public function createCard()
    {
        $card = $this->trelloManager->getCard();
        $card->setBoard($this->getBoard());

        return $card;
    }

    /**
     * Add Github issues to a Trello card
     *
     * @param CardInterface          $card
     * @param array|IssueInterface[] $issues
     */
    public function addIssuesToCard(CardInterface $card, array $issues)
    {
        if (!$card->hasChecklist(self::GITHUB_CHECKLIST_NAME)) {
            $card->addChecklist(self::GITHUB_CHECKLIST_NAME);
        }

        $checklist = $card->getChecklist(self::GITHUB_CHECKLIST_NAME);

        foreach ($issues as $issue) {
            $checklist->setItem($issue->getHtmlUrl(), $issue->isClosed());
        }

        $checklist->save();
    }

    /**
     * Get issues associated to a given card
     *
     * @param CardInterface $card
     *
     * @return IssueInterface[]
     */
    public function getIssuesAssociatedToCard(CardInterface $card)
    {
        $issues = array();
        $issueIds = array();

        foreach ($card->getChecklists() as $checklist) {
            if ($checklist->getName() === self::GITHUB_CHECKLIST_NAME) {
                foreach ($checklist->getItems() as $item) {
                    $issueIds = array_merge($issueIds, static::extractIssueIdsFromString($item['name']));
                }
            }
        }

        foreach ($issueIds as $issueId) {
            $issues[] = $this->getIssue($issueId);
        }

        return $issues;
    }

    public function isIssueAssociatedToCard(IssueInterface $issue, CardInterface $card)
    {
        foreach ($this->getIssuesAssociatedToCard($card) as $associatedIssue) {
            if ($associatedIssue->getId() === $issue->getId()) {
                return true;
            }
        }

        return false;
    }

    public function synchroniseIssueCompletionWithCard(IssueInterface $issue, CardInterface $card)
    {
        $card
            ->getChecklist(Scrummer::GITHUB_CHECKLIST_NAME)
            ->setItem($issue->getHtmlUrl(), $issue->isClosed())
            ->save();
    }

    /**
     * Add Trello cards to Github issue
     *
     * @param IssueInterface        $issue
     * @param array|CardInterface[] $cards
     */
    public function addCardsToIssue(IssueInterface $issue, array $cards)
    {
        foreach ($cards as $key => $card) {
            unset($cards[$key]);
            $cards[$card->getId()] = $card;
        }

        $body = $issue->getBody();

        foreach ($this->extractCardIdsFromString($body) as $cardId) {
            $cards[$cardId] = $this->getCard($cardId);
        }

        $body = trim(preg_replace(self::TRELLO_LINK_REGEX, '', $body))."\n";

        foreach ($cards as $card) {
            $body .= "\n - [".($card->isClosed() ? 'X' : ' ').'] '.$card->getUrl();
        }

        $issue->setBody($body);
        $issue->save();
    }

    /**
     * Get cards associated to a given issue
     *
     * @param IssueInterface $issue
     *
     * @return CardInterface[]
     */
    public function getCardsAssociatedToIssue(IssueInterface $issue)
    {
        $cards = array();
        $cardIds = static::extractCardIdsFromString($issue->getDescription());

        foreach ($cardIds as $cardId) {
            $cards[] = $this->getCard($cardId);
        }

        return $cards;
    }

    public function isCardAssociatedToIssue(CardInterface $card, IssueInterface $issue)
    {
        foreach ($this->getCardsAssociatedToIssue($issue) as $associatedCard) {
            if ($associatedCard->getId() === $card->getId()) {
                return true;
            }
        }

        return false;
    }

    public function setCardCompletionOnIssue(CardInterface $card, IssueInterface $issue)
    {
        // $issue
        //     ->getChecklist(Scrummer::GITHUB_CHECKLIST_NAME)
        //     ->setItem($issue->getHtmlUrl(), $issue->isClosed())
        //     ->save();
    }

    public static function extractIssueIdsFromString($string)
    {
        if (preg_match_all("/\/issues\/(\w+)/", $string, $issueIds)) {
            return $issueIds[1];
        }

        return array();
    }

    public static function extractCardIdsFromString($string)
    {
        if (preg_match_all(self::TRELLO_LINK_REGEX, $string, $cardIds)) {
            return end($cardIds);
        }

        return array();
    }
}
