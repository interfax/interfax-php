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


namespace Interfax;

use \GuzzleHttp\Client as GuzzleClient;
use Interfax\Exception\RequestException;
use Interfax\Outbound\Fax;
use Psr\Http\Message\ResponseInterface;

class Client
{
    /**
     * @var GenericFactory
     */
    private $factory;

    /**
     * @var Outbound
     */
    protected $outbound;
    /**
     * @var Inbound
     */
    protected $inbound;

    protected static $ENV_USERNAME = 'INTERFAX_USERNAME';
    protected static $ENV_PASSWORD = 'INTERFAX_PASSWORD';
    protected static $DEFAULT_BASE_URI = 'https://rest.interfax.net/';

    public $username;
    public $password;

    /**
     * @var GuzzleClient
     */
    protected $http;

    /**
     * Client constructor.
     * @param array $params
     * @param GenericFactory|null $factory - allows for testing injection with abstract class instantiation
     * @throws \InvalidArgumentException
     */
    public function __construct($params = [], GenericFactory $factory = null)
    {
        if ($params === null || !is_array($params)) {
            throw new \InvalidArgumentException('array of parameters expected to instantiate ' . __CLASS__);
        }

        $username = array_key_exists('username', $params) ? $params['username'] : getenv(static::$ENV_USERNAME);
        $password = array_key_exists('password', $params) ? $params['password'] : getenv(static::$ENV_PASSWORD);
        
        $this->username = $username;
        $this->password = $password;
        if ($this->username === '' || $this->password === '') {
            throw new \InvalidArgumentException('Username and Password must be provided or defined as environment variables ' . static::$ENV_USERNAME .' & ' . static::$ENV_PASSWORD);
        }

        // if its not injected, we instantiate directly
        if ($factory === null) {
            $factory = new GenericFactory();
        }

        $this->factory = $factory;
    }

    private $cached_accessible = [
        'outbound' => 'Interfax\Outbound',
        'inbound' => 'Interfax\Inbound'
    ];

    /**
     * Simplifies the route to accessing specific 'route' classes for the client
     *
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        if (in_array($name, array_keys($this->cached_accessible))) {
            if (!$this->$name) {
                $this->$name = $this->factory->instantiateClass($this->cached_accessible[$name], [$this]);
            }
            return $this->$name;
        }
    }

    /**
     * @return GuzzleClient
     */
    protected function getHttpClient()
    {
        if (!$this->http) {
            $this->http = $this->factory->instantiateClass(
                'GuzzleHttp\Client',
                [['base_uri' => static::$DEFAULT_BASE_URI]]
            );
        }

        return $this->http;
    }

    /**
     * @param array $params
     * @return array
     */
    protected function parseQueryParams($params = [])
    {
        if (array_key_exists('query', $params)) {
            foreach ($params['query'] as $k => $v) {
                if (is_bool($v)) {
                    $params['query'][$k] = $v ? 'TRUE' : 'FALSE';
                }
            }
        }
        return $params;
    }

    /**
     * POST request
     *
     * @param $uri
     * @param array $params
     * @param $multipart
     * @return string|array
     * @throws RequestException
     */
    public function post($uri, $params = [], $multipart = [])
    {
        $params = array_merge($params, ['multipart' => $multipart, 'auth' => [$this->username, $this->password]]);
        try {
            return $this->parseResponse($this->getHttpClient()->request('POST', $uri, $this->parseQueryParams($params)));
        }
        catch (\GuzzleHttp\Exception\RequestException $e) {
            throw RequestException::create('Problem with POST request', $e);
        }

    }

    /**
     * GET request.
     *
     * @param $uri
     * @param array $params
     * @return string|array
     * @throws RequestException
     */
    public function get($uri, $params = [])
    {
        $params = array_merge($params, ['auth' => [$this->username, $this->password]]);

        try {
            $response = $this->getHttpClient()->request('GET', $uri, $this->parseQueryParams($params));
            return $this->parseResponse($response);
        }
        catch (\GuzzleHttp\Exception\RequestException $e) {
            throw RequestException::create('Problem with GET request', $e);
        }

    }

    /**
     * Parses the responses in a consistent manner for handling by various classes.
     *
     * @param ResponseInterface $response
     * @return mixed|string|array
     * @throws RequestException
     */
    protected function parseResponse(ResponseInterface $response)
    {
        if (in_array($response->getStatusCode(), [200, 201], true)) {
            if ($location = $response->getHeaderLine('Location')) {
                return $location;
            } elseif ($response->getHeaderLine('Content-Type') === 'text/json') {
                return json_decode((string) $response->getBody(), true);
            } else {
                return $response->getBody();
            }
        }
        else {
            throw new RequestException('Unexpected response code', RequestException::$UNEXPECTED_RESPONSE_CODE, $response->getStatusCode());
        }
    }

    /**
     * @param $params
     * @return Fax
     * @throws \InvalidArgumentException
     *
     */
    public function deliver($params)
    {
        $delivery = $this->factory->instantiateClass('Interfax\Outbound\Delivery', [$this, $params]);
        return $delivery->send();
    }

    /**
     * @return string
     * @throws RequestException
     */
    public function getBalance()
    {
        return $this->get('/accounts/self/ppcards/balance');
    }
}