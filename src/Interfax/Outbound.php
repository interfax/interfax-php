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
use Interfax\Outbound\Fax;

class Outbound
{
    /**
     * @var GenericFactory
     */
    private $factory;

    protected $client;

    public function __construct(Client $client, GenericFactory $factory = null)
    {
        $this->client = $client;
        if ($factory === null) {
            $factory = new GenericFactory();
        }
        $this->factory = $factory;
    }



    /**
     * @param array $definitions
     * @return Fax[]
     * @throws \InvalidArgumentException
     */
    protected function createFaxes($definitions)
    {
        $res = [];
        foreach ($definitions as $f_data) {
            if (array_key_exists('id', $f_data)) {
                $res[] = $this->factory->instantiateClass(
                    'Interfax\Outbound\Fax',
                    [$this->client, $f_data['id'], $f_data]
                );
            } else {
                throw new \InvalidArgumentException('No id attribute found in fax definition');
            }
        }

        return $res;
    }

    /**
     * @param array $ids
     * @return Fax[]
     * @throws \InvalidArgumentException
     * @throws RequestException
     */
    public function completed($ids = [])
    {
        $params = [];
        if (count($ids) === 0) {
            throw new \InvalidArgumentException('Must provide at least one id for completed request');
        }

        $params['query'] = ['ids' => implode(',', $ids)];

        $json = $this->client->get('/outbound/faxes/completed', $params);
        return $this->createFaxes($json);
    }

    /**
     * @param array $query_params
     * @return Outbound\Fax[]
     * @internal param $params
     */
    public function recent($query_params = [])
    {
        $params = [];
        if (count($query_params)) {
            $params = ['query' => $query_params];
        }
        return $this->createFaxes($this->client->get('/outbound/faxes', $params));
    }


    /**
     * @param $id
     * @param null $fax_number
     * @return mixed
     * @throws RequestException
     */
    public function resend($id, $fax_number = null)
    {
        $fax = $this->factory->instantiateClass('Interfax\Outbound\Fax', [$this->client, $id]);

        return $fax->resend($fax_number);
    }

    /**
     * Get an individual Fax resource for the given $id.
     *
     * @param $id
     * @return null|Interfax\Outbound\Fax
     */
    public function find($id)
    {
        try {
            $response = $this->client->get('/outbound/faxes/' . $id);

            if (is_array($response) && array_key_exists('id', $response)) {
                return $this->factory->instantiateClass(
                    'Interfax\Outbound\Fax',
                    [$this->client, $response['id'], $response]
                );
            }
        } catch (\RuntimeException $e) {
            if ((int) $e->getStatusCode() === 404) {
                return null;
            }
            throw $e;
        }

        throw new \RuntimeException('A reasonable but unhandled response was received');
    }

    /**
     * @param array $params
     * @return Outbound\Fax[]
     * @throws \InvalidArgumentException
     * @throws RequestException
     */
    public function search($params = [])
    {
        return $this->createFaxes($this->client->get('/outbound/search', ['query' => $params]));
    }
}
