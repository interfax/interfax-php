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
use \GuzzleHttp\Psr7\Request;
use Interfax\Outbound\Delivery;
use Interfax\Outbound\Fax;
use Psr\Http\Message\ResponseInterface;

class Client
{
    protected static $ENV_USERNAME = 'INTERFAX_USERNAME';
    protected static $ENV_PASSWORD = 'INTERFAX_PASSWORD';
    protected static $DEFAULT_BASE_URI = 'https://rest.interfax.net/';

    public $username;
    public $password;

    /**
     * @var GuzzleClient
     */
    protected $http;

    public function __construct($params = [])
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
    }

    /**
     * Provides for dependency injection of GuzzleHttp Client for testing purposes.
     *
     * @param GuzzleClient $client
     */
    public function setHttpClient(GuzzleClient $client)
    {
        $this->http = $client;
    }

    /**
     * @return GuzzleClient
     */
    protected function getHttpClient()
    {
        if (!$this->http) {
            $this->http = new GuzzleClient([
                'base_uri' => static::$DEFAULT_BASE_URI
            ]);
        }

        return $this->http;
    }

    private $accessible = ['outbound', 'inbound'];

    /**
     * Simplifies the route to accessing specific 'route' classes for the client
     *
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        if (in_array($name, $this->accessible)) {
            return $this->{'get' . ucfirst($name)}();
        }
    }

    /**
     * mockable method to get a Delivery instance.
     *
     * @param $params
     * @return Delivery
     * @throws \InvalidArgumentException
     */
    public function getDelivery($params)
    {
        return new Delivery($this, $params);
    }

    /**
     * Mockable method to get Outbound instance.
     *
     * @return Outbound
     */
    public function getOutbound()
    {
        return new Outbound($this);
    }

    /**
     * Parses the responses in a consistent manner for handling by various classes.
     *
     * @param ResponseInterface $response
     * @return mixed|string
     * @throws \Exception
     */
    protected function parseResponse(ResponseInterface $response)
    {
        if (in_array($response->getStatusCode(), [200, 201], true)) {
            if ($location = $response->getHeaderLine('Location')) {
                return $location;
            } elseif ($response->getHeaderLine('Content-Type') === 'text/json') {
                return json_decode((string) $response->getBody(), true);
            } else {
                return (string) $response->getBody();
            }
        }
        else {
            // TODO: better exceptions
            throw new \Exception("Unexpected response code: " . $response->getStatusCode());
        }
    }

    /**
     * POST request
     *
     * @param $uri
     * @param array $params
     * @param $multipart
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function post($uri, $params = [], $multipart = [])
    {
        $params = array_merge($params, ['multipart' => $multipart, 'auth' => [$this->username, $this->password]]);

        return $this->parseResponse($this->getHttpClient()->request('POST', $uri, $params));
    }

    /**
     * GET request.
     *
     * @param $uri
     * @param array $params
     * @return string|array
     */
    public function get($uri, $params = [])
    {
        $params = array_merge($params, ['auth' => [$this->username, $this->password]]);

        return $this->parseResponse($this->getHttpClient()->request('GET', $uri, $params));
    }

    /**
     * @param $params
     * @return Fax
     * @throws \InvalidArgumentException
     *
     */
    public function deliver($params)
    {
        $delivery = $this->getDelivery($params);
        return $delivery->send();
    }

    /**
     * @return string
     */
    public function getBalance()
    {
        return $this->get('/accounts/self/ppcards/balance');
    }
}