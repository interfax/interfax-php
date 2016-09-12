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

use Interfax\Outbound\Fax;

class Outbound
{
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param $definition
     * @return Fax
     * @throws \InvalidArgumentException
     */
    public function createFax($definition)
    {
        if (!array_key_exists('id', $definition)) {
            throw new \InvalidArgumentException('Missing required definition parameter "id"');
        }

        return new Fax($this->client, $definition['id'], $definition);
    }

    /**
     * @param array $definitions
     * @return Fax[]
     * @throws \InvalidArgumentException
     */
    public function createFaxes($definitions)
    {
        $res = [];
        foreach ($definitions as $f_data) {
            if ($fax = $this->createFax($f_data)) {
                $res[] = $fax;
            }
        }

        return $res;
    }

    /**
     * @param array $ids
     * @return Fax[]
     * @throws \InvalidArgumentException
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

}