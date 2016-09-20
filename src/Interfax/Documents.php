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

    public function create($filename, $size, $params = [])
    {
        $all_params = array_merge($params, [
            'name' => $filename,
            'size' => $size
        ]);

        $location = $this->client->post('/outbound/documents', ['query' => $all_params]);

        $path = parse_url($location, PHP_URL_PATH);
        $bits = explode('/', $path);
        $id = array_pop($bits);
        $all_params['id'] = $id;
        // spoof the attributes for the Document object from the given parameters
        return $this->factory->instantiateClass('Interfax\Document', [$this->client, $id, $all_params]);
    }

    /**
     * @return array
     */
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