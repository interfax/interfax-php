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

class Fax
{
    /**
     * @var Client
     */
    protected $client;
    protected $resource_id;
    protected $record;

    public function __construct(Client $client, $id, $definition = [])
    {
        $this->client = $client;
        $this->resource_uri = '/inbound/faxes/' . $id;
        $this->record = ['messageId' => $id];
        foreach ($definition as $k => $v) {
            $this->record[$k] = $v;
        }
    }

    /**
     * @param bool $unread
     * @return bool
     * @throws \Exception
     */
    protected function mark($unread = true)
    {
        $this->client->post($this->resource_uri, ['unread' => $unread]);

        // lack of exception indicates success
        return true;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function markRead()
    {
        return $this->mark(false);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function markUnread()
    {
        return $this->mark(true);
    }

}