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

abstract class Resource
{
    /**
     * @var GenericFactory
     */
    protected $factory;

    /**
     * @var Client
     */
    protected $client;
    protected $resource_id;
    protected $record;

    protected static $resource_uri_stem;

    public function __construct(Client $client, $id, $definition = [], GenericFactory $factory = null)
    {
        $this->client = $client;

        $this->resource_uri = static::$resource_uri_stem . $id;

        foreach ($definition as $k => $v) {
            $this->record[$k] = $v;
        }

        if ($factory === null) {
            $factory = new GenericFactory();
        }
        $this->factory = $factory;
    }
}