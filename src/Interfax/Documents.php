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

    /**
     * @param $document_name - name used when defining document on the API
     * @param $size - in bytes
     * @param array $params - additional parameters to be used when creating the document on the API
     * @return Document
     * @throws \Interfax\Exception\RequestException
     */
    public function create($document_name, $size, $params = [])
    {
        $all_params = array_merge($params, [
            'name' => $document_name,
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
     * Get list of the available documents previously uploaded.
     *
     * @return array
     */
    public function available($query_params = [])
    {
        $params = [];
        if (count($query_params)) {
            $params = ['query' => $query_params];
        }

        $response = $this->client->get('/outbound/documents', $params);

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
