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

    public function __construct(Client $client, GenericFactory $factory = null)
    {
        $this->client = $client;
        if ($factory === null) {
            $factory = new GenericFactory();
        }
        $this->factory = $factory;
    }

    /**
     * Retrieve a list of incoming faxes for the Client account
     *
     * @param array $query_params
     * @return Inbound\Fax[]
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

        //TODO: make this better
        throw new \Exception('unexpected result');
    }
}