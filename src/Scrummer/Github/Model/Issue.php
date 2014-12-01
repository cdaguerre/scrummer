<?php

namespace Scrummer\Github\Model;

use Github\Client;
use Github\Exception\BadMethodCallException;

class Issue implements IssueInterface
{
    const LINK_REGEX = "/https:\/\/github.com\/(\w+)\/(\w+)\/issues\/(\w+)/";

    protected $client;
    public $data;
    public $number;
    public $organization;
    public $repository;

    /**
     * Constructor.
     *
     * @param Client $client    Github client
     * @param array  $issueData Result if Github API show issue call
     */
    public function __construct(Client $client, array $issueData)
    {
        $this->client = $client;
        $this->parseData($issueData);
    }

    /**
     * {@inheritdoc}
     */
    public function updateFromCard(CardInterface $card)
    {
        $this->setTitle($card->getName());
        $this->setBody($card->getDesc());

        return $this;

        // switch ($card->getListName()) {
        //     case 'PRODUCT BACKLOG':
        //     case 'SPRINT BACKLOG':
        //     case 'DOING':
        //         $this->data['state'] = 'open';
        //         break;
        //     case 'STAGE':
        //     case 'DONE':
        //         $this->data['state'] = 'closed';
        //         break;
        //     default:
        //         $this->data['state'] = 'open';
        // }

        // if ($card->getClosed()) {
        //     $this->data['state'] = 'closed';
        // }

        // return $this->save();
    }

    public function setDescription($description)
    {
        $this->data['body'] = $description;

        return $this;
    }

    public function getDescription()
    {
        return $this->data['body'];
    }

    public function setBody($text)
    {
        // $cardIds = Card::extractCardIdsFromString($this->getBody());

        // $cardIds = array_unique($cardIds);

        // $text = trim(preg_replace(Card::LINK_REGEX, '', $text))."\n";

        // foreach ($cardIds as $cardId) {
        //     $text .= "\n".Card::getHtmlUrlFor($cardId);
        // }

        $this->data['body'] = $text;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getNumber();
    }

    /**
     * {@inheritdoc}
     */
    public function getHtmlUrl()
    {
        return $this->getHtmlUrlFor($this->number, $this->repository, $this->organization);
    }

    /**
     * {@inheritdoc}
     */
    public function hasCard(CardInterface $card)
    {
        return in_array($card->getId(), $this->getCardIds());
    }

    /**
     * {@inheritdoc}
     */
    public function hasCards()
    {
        return count($this->getCardIds()) !== 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getCardIds()
    {
        return Card::extractCardIdsFromString($this->data['body']);
    }

    /**
     * {@inheritdoc}
     */
    public function addCard(CardInterface $card)
    {
        if (strlen(trim($this->data['body'])) !== 0) {
            $this->data['body'] .= "\n\n";
        }
        $this->data['body'] .= $card->getHtmlUrl();

        $this->setBody($this->data['body']);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        foreach ($this->data as $key => $component) {
            if (is_array($component)) {
                unset($this->data[$key]);
            }
        }

        if (isset($this->number)) {
            $this->parseData($this->client->api('issue')->update(
                $this->organization,
                $this->repository,
                $this->number,
                $this->data
            ));
        } else {
            $this->parseData($this->client->api('issue')->create(
                $this->organization,
                $this->repository,
                $this->data
            ));
        }

        return $this;
    }

    public function getLabels()
    {
        return $this->client->api('issue')->labels()->all($this->organization, $this->repository, $this->number);
    }

    public function clearLabels()
    {
        return $this->client->api('issue')->labels()->clear($this->organization, $this->repository, $this->number);
    }

    public function addLabel($label)
    {
        return $this->addLabels(array($label));
    }

    public function addLabels($labels)
    {
        $this->client->api('issue')->labels()->add($this->organization, $this->repository, $this->number, $labels);

        return $this;
    }

    public function removeLabel($label)
    {
        $this->client->api('issue')->labels()->remove($this->organization, $this->repository, $this->number, $label);

        return $this;
    }

    public function isComplete()
    {
        return $this->data['state'] !== 'closed';
    }

    public function setComplete($bool = true)
    {
        $this->data['state'] = $bool ? 'closed' : 'open';

        return $this;
    }

    private function parseData(array $data)
    {
        if (isset($data['url'])) {
            $comps = explode('/', substr($data['url'], strpos($data['url'], '/repos/') + 7));

            if (count($comps) > 1) {
                $this->organization = $comps[0];
                $this->repository = $comps[1];
            }
        }

        if (isset($data['organization'])) {
            $this->organization = $data['organization'];
        }
        if (isset($data['repository'])) {
            $this->repository = $data['repository'];
        }
        if (isset($data['number'])) {
            $this->number = $data['number'];
        }

        if (!isset($data['body'])) {
            $data['body'] = null;
        }

        $this->data = $data;
    }

    public function __call($method, $arguments)
    {
        if (substr($method, 0, 3) === 'get') {
            $property = lcfirst(substr($method, 3));
            if (array_key_exists($property, $this->data)) {
                return $this->data[$property];
            }
        }

        if (substr($method, 0, 3) === 'set') {
            $property = lcfirst(substr($method, 3));
            $this->data[$property] = end($arguments);

            return $this;
        }

        throw new BadMethodCallException();
    }

    /**
     * {@inheritdoc}
     */
    public static function getHtmlUrlFor($id, $repository, $organization)
    {
        return 'https://github.com/'.$organization.'/'.$repository.'/issues/'.$id;
    }

    public static function extractIssueIdsFromString($string)
    {
        if (preg_match_all("/\/issues\/(\w+)/", $string, $issueIds)) {
            return $issueIds[1];
        }

        return array();
    }
}
