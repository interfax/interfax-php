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



class Fax
{
    protected $status;
    protected $resource_uri;

    public function __construct(\GuzzleHttp\Psr7\Response $response)
    {
        if ($response->getStatusCode() != 201) {
            throw new \InvalidArgumentException('Unexpected response status code for new ' . __CLASS__);
        }
        if (!$location = $response->getHeaderLine('Location')) {
            throw new \InvalidArgumentException('Response did not contain a resource location for new ' . __CLASS__);
        }

        $this->resource_uri = $location;
        $this->status = (string)$response->getBody();
    }

    public function getStatus()
    {
        return (integer)$this->status;
    }
}