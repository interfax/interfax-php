<?php
/**
 * Interfax
 *
 * (C) InterFAX, 2016
 *
 * @package interfax/interfax
 * @author Interfax <dev@interfax.net>
 * @author Mike Smith <mike.smith@camc-ltd.co.uk>
 * @copyright Copyright (c) 2016, InterFAX
 * @license MIT
 */
namespace Interfax;

use Interfax\Exception\RequestException;

class Inbound
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var GenericFactory
     */
    protected $factory;

    /**
     * Inbound constructor.
     *
     * @param Client $client
     * @param GenericFactory|null $factory
     */
    public function __construct(Client $client, GenericFactory $factory = null)
    {
        $this->client = $client;
        if ($factory === null) {
            $factory = new GenericFactory();
        }
        $this->factory = $factory;
    }

    /**
     * Retrieve a list of incoming faxes for the Client account.
     *
     * @param array $query_params
     * @return Inbound\Fax[]
     * @throws \RuntimeException
     */
    public function incoming($query_params = [])
    {
        $params = [
            'query' => $query_params
        ];

        $json = $this->client->get('/inbound/faxes', $params);

        if (is_array($json)) {
            $result = [];
            foreach ($json as $incoming) {
                $id = $incoming['messageId'];
                $result[] = $this->factory->instantiateClass('Interfax\Inbound\Fax', [$this->client, $id, $incoming]);
            }
            return $result;
        }

        throw new \RuntimeException('A reasonable but unhandled response was received');
    }

    /**
     * Search for an incoming fax with the given id.
     *
     * @param $id
     * @return \Interfax\Inbound\Fax|null
     * @throws RequestException
     * @throws \RuntimeException
     */
    public function find($id)
    {
        try {
            $json = $this->client->get('/inbound/faxes/' . $id);

            if (is_array($json)) {
                return $this->factory->instantiateClass('Interfax\Inbound\Fax', [$this->client, $id, $json]);
            }
        } catch (RequestException $e) {
            //TODO: test me
            if ((int) $e->getStatusCode() === 404) {
                return null;
            }
            throw $e;
        }

        throw new \RuntimeException('A reasonable but unhandled response was received');
    }
}
