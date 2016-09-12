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


class Delivery
{
    protected static $required_qparams = ['faxNumber'];
    protected $query_params = [];

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

        if (!array_key_exists('file', $params) && !array_key_exists('files', $params)) {
            throw new \InvalidArgumentException('must provide a file or files for Delivery');
        }

        $missing_qparams = array_diff(static::$required_qparams, array_keys($params));

        if (count($missing_qparams)) {
            throw new \InvalidArgumentException('missing required query parameters ' . implode(', ', $missing_qparams));
        }

        foreach ($params as $k => $v) {
            $this->query_params[$k] = $v;
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
     * @param Outbound\Delivery $fax
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function send()
    {
        $http = $this->getHttpClient();

        $params = [
            'auth' => [$this->username, $this->password, 'digest'],
            'headers' => [
                'Content-Type' => $fax->getContentType()
            ],
            'query' => $fax->getQueryParams(),
            'body' => $fax->getBody()
        ];

        return $http->request('POST', 'outbound/fax', $params);
    }
}