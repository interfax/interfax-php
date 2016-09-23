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
     * @return bool
     * @throws \Interfax\Exception\RequestException
     */
    public function upload($start, $end, $data)
    {
        $params = [
            'headers' => [
                'Range' => 'bytes=' . $start . '-' . $end,
                'Content-Length' => strlen($data)
            ],
            'body' => $data
        ];

        $this->client->post($this->resource_uri, $params);

        return true;
    }

    public function cancel()
    {
        $this->client->delete($this->resource_uri);
        $this->record = [];
        return true;
    }
}