<?php
/**
 * Interfax
 *
 * (C) InterFAX, 2016
 *
 * @package interfax/interfax
 * @author Interfax <dev@interfax.net>
 * @copyright Copyright (c) 2016, InterFAX
 * @license MIT
 */


namespace Interfax\Outbound;

use Interfax\Client;
use Interfax\Exception\RequestException;
use \Interfax\File;
use Interfax\GenericFactory;

class Delivery
{
    protected static $required_qparams = ['faxNumber'];
    protected $query_params = [];
    /**
     * @var File[]
     */
    protected $files = [];
    /**
     * @var \Interfax\Client
     */
    private $client;

    /**
     * Delivery constructor.
     *
     * @param \Interfax\Client $client
     * @param array $params
     * @param GenericFactory $factory
     * @throws \InvalidArgumentException
     */
    public function __construct(Client $client, $params = [], GenericFactory $factory = null)
    {
        $this->client = $client;

        $missing_qparams = array_diff(static::$required_qparams, array_keys($params));

        if (count($missing_qparams)) {
            throw new \InvalidArgumentException('missing required query parameters ' . implode(', ', $missing_qparams));
        }

        if ($factory === null) {
            $factory = new GenericFactory();
        }

        $this->factory = $factory;

        $this->resolveFiles($params);

        foreach ($params as $k => $v) {
            $this->query_params[$k] = $v;
        }
    }

    /**
     * Runs through the file/files parameter of params and instantiates File objects for delivery.
     *
     * @param $params
     * @throws \InvalidArgumentException
     */
    protected function resolveFiles(&$params)
    {
        // normalise to a single array of files
        $files = [];
        if (isset($params['file'])) {
            if (isset($params['files'])) {
                throw new \InvalidArgumentException('Can only provide file or files for ' . __CLASS__);
            }
            $files[] = $params['file'];
            unset($params['file']);
        } elseif (isset($params['files'])) {
            $files = $params['files'];
            unset($params['files']);
        } else {
            throw new \InvalidArgumentException('Must provide a file or files for Delivery');
        }

        // create file objects where necessary
        foreach ($files as $f) {
            if (is_object($f)) {
                $cls = get_class($f);
                if (is_a($f, 'Interfax\File')) {
                    $this->files[] = $f;
                } elseif (is_a($f, 'Interfax\Document')) {
                    $this->files[] = $this->factory->instantiateClass(
                        'Interfax\File',
                        [$this->client, $f->getHeaderLocation()]
                    );
                } else {
                    throw new \InvalidArgumentException(
                        'File objects must be Interfax\File or Interfax\Document objects for Delivery. not ' . $cls
                    );
                }
            } elseif (is_array($f)) {
                $args = array_merge([$this->client], $f);
                $this->files[] = $this->factory->instantiateClass('Interfax\File', $args);
            } else {
                // assumed to be a path
                $this->files[] = $this->factory->instantiateClass('Interfax\File', [$this->client, $f]);
            }
        }
    }

    /**
     * @return string
     */
    public function getMultipart()
    {
        $multipart = [];
        foreach ($this->files as $i => $file) {
            $multipart[] = [
                'name' => 'file'.$i,
                'filename' => urlencode($file->getName()),
                'contents' => $file->getBody(),
                'headers' => $file->getHeader(),
            ];
        }
        return $multipart;
    }

    /**
     * @return Fax
     * @throws \InvalidArgumentException
     * @throws RequestException
     */
    public function send()
    {
        $params = [
            'query' => $this->query_params,
        ];

        if (count($this->files) > 1) {
            $location = $this->client->post('/outbound/faxes', $params, $this->getMultipart());
        }
        else {
            $params['headers'] = $this->files[0]->getHeader();
            $params['body'] = $this->files[0]->getBody();
            $location = $this->client->post('/outbound/faxes', $params);
        }

        // retrieve ID (last element of location path) for outbound fax object
        $path = parse_url($location, PHP_URL_PATH);
        $bits = explode('/', $path);
        return $this->factory->instantiateClass('Interfax\Outbound\Fax', [$this->client, array_pop($bits)]);
    }
}
