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
use \Interfax\File;
use Interfax\GenericFactory;
use Psr\Http\Message\ResponseInterface;

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
     * @throws \InvalidArgumentException
     */
    public function __construct(\Interfax\Client $client, $params = [], GenericFactory $factory = null)
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
        $files = [];
        if (array_key_exists('file', $params)) {
            if (array_key_exists('files', $params)) {
                throw new \InvalidArgumentException('Can only provide file or files for ' . __CLASS__);
            }
            $files[] = $params['file'];
            unset($params['file']);
        } elseif (array_key_exists('files', $params)) {
            $files = $params['files'];
            unset($params['files']);
        } else {
            throw new \InvalidArgumentException('must provide a file or files for Delivery');
        }

        foreach ($files as $f) {
            if (is_array($f)) {
                if (count($f) == 2) {
                    $this->files[] = $this->factory->instantiateClass('Interfax\File', [$f[0], $f[1]]);
                }
            }
            else {
                $this->files[] = $this->factory->instantiateClass('Interfax\File', [$f]);
            }
        }
    }

    /**
     * @return string
     */
    public function getMultipart()
    {
        $multipart = [];
        foreach ($this->files as $file) {
            $multipart[] = [
                'name' => $file->getName(),
                'contents' => $file->getBody(),
                'headers' => $file->getHeader()
            ];
        }
        return $multipart;
    }

    /**
     * @return Fax
     * @throws \InvalidArgumentException
     */
    public function send()
    {
        $params = [
            'query' => $this->query_params,
        ];

        $location = $this->client->post('outbound/faxes', $params, $this->getMultipart());
        // TODO: clean this up
        $path = parse_url($location, PHP_URL_PATH);
        $bits = explode('/', $path);
        return $this->factory->instantiateClass('Interfax\Outbound\Fax', [$this->client, array_pop($bits)]);
    }
}