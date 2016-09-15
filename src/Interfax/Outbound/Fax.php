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
     * @var GenericFactory
     */
    private $factory;

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
     * @param integer $id
     * @param array $definition
     * @throws \InvalidArgumentException
     */
    public function __construct(Client $client, $id, $definition = [])
    {
        $this->client = $client;
        $this->resource_uri = '/outbound/faxes/' . $id;
        $this->record = ['id' => $id];
        foreach ($definition as $k => $v) {
            $this->record[$k] = $v;
        }
    }

    /**
     * Request the details of this Fax from the api and update the record structure accordingly
     */
    protected function updateRecord()
    {
        $response = $this->client->get($this->resource_uri);
        $this->record = $response;
        $this->status = (integer) $this->record['status'];
    }

    /**
     * If the current status of the fax is not OK (Fax succesfully sent), the status will be refreshed before returning
     * the status (unless $reload is false)
     *
     * @TODO: move the update process?
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

    /**
     * @param $name
     * @return mixed|null
     * @throws \OutOfBoundsException
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->record)) {
            return $this->record[$name];
        }
        throw new \OutOfBoundsException($name . ' is not a property of ' . __CLASS__);
    }

}