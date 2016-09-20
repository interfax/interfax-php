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

class Documents
{
    /**
     * @var GenericFactory
     */
    private $factory;

    /**
     * @var Client
     */
    protected $client;

    public function __construct(Client $client, GenericFactory $factory = null)
    {
        $this->client = $client;
        if ($factory === null) {
            $factory = new GenericFactory();
        }
        $this->factory = $factory;
    }

    public function available()
    {
        $response = $this->client->get('/outbound/documents');

        if (is_array($response)) {
            $result = [];
            foreach ($response as $definition) {
                $result[] = $this->factory->instantiateClass('Interfax\Document', [$this->client, $definition]);
            }
            return $result;
        }

        throw new \RuntimeException('A reasonable but unhandled response was received');
    }

}