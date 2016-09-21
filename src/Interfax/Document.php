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
                'Range' => 'bytes=' . $start . '-' . $end
            ],
            'body' => $data
        ];

        $this->client->post($this->resource_uri, $params, []);

        return true;
    }

    /**
     * Get current status. If $reload is false, current status without checking with the API endpoint, otherwise
     * the data is refreshed.
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

        try {
            return $this->status;
        } catch (\OutOfBoundsException $e) {
            // status not set
            return null;
        }
    }

}