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

use Interfax\Exception\RequestException;
use Interfax\GenericFactory;


class Fax
{
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
     * @param GenericFactory $factory
     * @throws \InvalidArgumentException
     */
    public function __construct(Client $client, $id, $definition = [], GenericFactory $factory = null)
    {
        $this->client = $client;
        $this->resource_uri = '/outbound/faxes/' . $id;
        $this->record = ['id' => $id];
        foreach ($definition as $k => $v) {
            $this->record[$k] = $v;
        }

        if ($factory === null) {
            $factory = new GenericFactory();
        }

        $this->factory = $factory;
    }

    /**
     * Request the details of this Fax from the api and update the record structure accordingly
     * @throws RequestException
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

    /**
     * @return string
     */
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

    /**
     * Resend the fax, possibly to a new fax number
     *
     * @param string $fax_number
     * @return $this
     * @throws RequestException
     */
    public function resend($fax_number = null)
    {
        $params = [];
        if ($fax_number !== null) {
            $params['query'] = ['faxNumber' => $fax_number];
        }

        $location = $this->client->post($this->resource_uri . '/resend', $params);

        $path = parse_url($location, PHP_URL_PATH);
        $bits = explode('/', $path);
        return $this->factory->instantiateClass(__CLASS__, [$this->client, array_pop($bits)]);
    }
}