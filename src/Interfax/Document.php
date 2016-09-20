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

class Document
{
    /**
     * @var Client
     */
    protected $client;

    public function __construct(Client $client, $id, $definition = [])
    {
        $this->client = $client;
    }
}