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
use Interfax\Image;
use Interfax\Resource;

class Fax extends Resource
{
    protected static $resource_uri_stem = '/outbound/faxes/';

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->resource_uri;
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

    /**
     * @return self
     * @throws RequestException
     */
    public function cancel()
    {
        $this->client->post($this->resource_uri . '/cancel');

        return $this;
    }

    /**
     * @return self
     * @throws RequestException
     */
    public function hide()
    {
        $this->client->post($this->resource_uri . '/hide');

        return $this;
    }

    /**
     * @return Image
     * @throws RequestException
     */
    public function image()
    {
        $response = $this->client->get($this->resource_uri . '/image');

        return $this->factory->instantiateClass('Interfax\Image', [$response]);
    }
}
