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

class Document extends Resource
{
    protected static $resource_uri_stem = '/outbound/documents/';

    /**
     * @param $start
     * @param $end
     * @param $data
     * @return self
     * @throws \Interfax\Exception\RequestException
     */
    public function upload($start, $end, $data)
    {
        $params = [
            'headers' => [
                'Range' => 'bytes=' . $start . '-' . $end,
                'Content-Length' => $end - $start + 1,
            ],
            'body' => $data
        ];

        $this->client->post($this->resource_uri, $params);

        return $this;
    }

    /**
     * @return self
     */
    public function cancel()
    {
        $this->client->delete($this->resource_uri);
        $this->record = [];
        return $this;
    }

    public function getHeaderLocation()
    {
        return $this->client->getBaseUri() . $this->resource_uri;
    }
}
