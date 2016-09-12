<?php
/**
 * Interfax
 *
 * (C) InterFAX, 2016
 *
 * @package interfax/interfax
 * @author Interfax <dev@interfax.net>
 * @copyright Copyright (c) 2016, InterFAX
 * @license MIT
 */

namespace Interfax\Outbound;

use Interfax\Client;
use GuzzleHttp\Psr7\Response;

class Fax
{
    /**
     * @var \Interfax\Client;
     */
    protected $client;

    protected $status;
    protected $resource_uri;
    protected $record;

    /**
     * Fax constructor.
     *
     * @param Client $client
     * @param \GuzzleHttp\Psr7\Response $response
     * @throws \InvalidArgumentException
     */
    public function __construct(Client $client, Response $response)
    {
        $this->client = $client;

        if ($response->getStatusCode() != 201) {
            throw new \InvalidArgumentException('Unexpected response status code ' . $response->getStatusCode() .' for new ' . __CLASS__);
        }
        if (!$location = $response->getHeaderLine('Location')) {
            throw new \InvalidArgumentException('Response did not contain a resource location for new ' . __CLASS__);
        }

        $this->resource_uri = $location;

        // casting as we don't want to read the body as a stream
        $this->status = (integer)(string)$response->getBody();
    }

    protected function parseResponse(Response $response)
    {
        if ($response->getStatusCode() != 200) {
            throw new \InvalidArgumentException('Unexpected response status code ' . $response->getStatusCode() .' for new ' . __CLASS__);
        }

        $this->record = json_decode((string) $response->getBody(), true);
        $this->status = (integer) $this->record['status'];
    }


    protected function updateRecord()
    {
        $path = parse_url($this->resource_uri)['path'];

        $response = $this->client->get($path);
        $this->parseResponse($response);
    }

    /**
     * If the current status of the fax is not OK (Fax succesfully sent), the status will be refreshed before returning
     * the status (unless $reload is false)
     *
     * @param boolean $reload
     * @return int
     */
    public function getStatus($reload = true)
    {
        if ($reload) {
            $this->updateRecord();
        }

        return $this->status;
    }

    public function getLocation()
    {
        return $this->resource_uri;
    }

}