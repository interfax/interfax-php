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
namespace Interfax\Inbound;

use Interfax\Client;
use Interfax\Exception\RequestException;
use Interfax\GenericFactory;
use Interfax\Resource;

class Fax extends Resource
{
    protected static $resource_uri_stem = '/inbound/faxes/';

    /**
     * @param bool $unread
     * @return bool
     * @throws RequestException
     */
    protected function mark($unread = true)
    {
        $this->client->post($this->resource_uri, ['query' => ['unread' => $unread]]);

        // lack of exception indicates success
        return true;
    }

    /**
     * @return bool
     * @throws RequestException
     */
    public function markRead()
    {
        return $this->mark(false);
    }

    /**
     * @return bool
     * @throws RequestException
     */
    public function markUnread()
    {
        return $this->mark(true);
    }

    /**
     * @param string $email
     * @return bool
     * @throws RequestException
     */
    public function resend($email = null)
    {
        $params = [];
        if ($email !== null) {
            $params['query'] = ['email' => $email];
        }
        $this->client->post($this->resource_uri . '/resend', $params);

        // lack of exception indicates success
        return true;
    }

    /**
     * @return \Interfax\Image
     * @throws RequestException
     */
    public function image()
    {
        $response = $this->client->get($this->resource_uri . '/image');

        return $this->factory->instantiateClass('Interfax\Image', [$response]);
    }

    /**
     * Returns an array of hasharrays with the structure returned from the emails endpoint.
     *
     * @return array
     * @throws RequestException
     */
    public function emails()
    {
        return $this->client->get($this->resource_uri . '/emails');
    }
}
