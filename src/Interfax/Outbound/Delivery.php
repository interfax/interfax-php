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
    public function __construct(\Interfax\Client $client, $params = [])
    {
        $this->client = $client;

        $missing_qparams = array_diff(static::$required_qparams, array_keys($params));

        if (count($missing_qparams)) {
            throw new \InvalidArgumentException('missing required query parameters ' . implode(', ', $missing_qparams));
        }

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
                    $this->files[] = new File($f[0], $f[1]);
                }
            }
            else {
                $this->files[] = new File($f);
            }
        }
    }

    /**
     * @return array
     */
    public function getQueryParams()
    {
        return $this->query_params;
    }

    /**
     * @TODO: implement
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
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return Fax
     */
    public function createFax(\Psr\Http\Message\ResponseInterface $response)
    {
        return new Fax($this->client, $response);
    }

    /**
     * @return Fax
     */
    public function send()
    {
        $params = [
            'query' => $this->query_params,
        ];

         return $this->createFax($this->client->post('outbound/faxes', $params, $this->getMultipart()));
    }
}