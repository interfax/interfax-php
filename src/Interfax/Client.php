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

    /**
     * mockable method to get a Delivery instance.
     *
     * @param $params
     * @return Delivery
     */
    public function getDeliveryInstance($params)
    {
        return new Delivery($this, $params);
    }

    /**
     * @param $uri
     * @param array $params
     * @param $multipart
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function post($uri, $params = [], $multipart = [])
    {
        $params = array_merge($params, ['multipart' => $multipart, 'auth' => [$this->username, $this->password]]);

        return $this->getHttpClient()->request('POST', $uri, $params);
    }

    /**
     * @param $params
     */
    public function deliver($params)
    {
        $delivery = $this->getDeliveryInstance($params);
        return $delivery->send();
    }

}