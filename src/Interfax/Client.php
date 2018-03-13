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
    const VERSION = '1.1.4';

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
    /**
     * @var Documents
     */
    protected $documents;

    protected static $ENV_USERNAME = 'INTERFAX_USERNAME';
    protected static $ENV_PASSWORD = 'INTERFAX_PASSWORD';
    protected static $DEFAULT_BASE_URI = 'https://rest.interfax.net';

    public $username;
    public $password;

    /**
     * @var GuzzleClient
     */
    protected $http;

    /**
     * @var bool
     */
    private $debug = false;
    private $base_uri;

    /**
     * Client constructor.
     * @param array $params
     *          'username' - string
     *          'password' - string
     *          'base_uri' - string override the API endpoint base (useful for testing)
     *          'debug' - bool - put the Guzzle client into debug mode
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

        if (array_key_exists('debug', $params)) {
            $this->debug = $params['debug'];
        }
        if (array_key_exists('base_uri', $params)) {

            $this->base_uri = rtrim($params['base_uri'], '/');
        }

        $this->username = $username;
        $this->password = $password;
        if ($this->username === '' || $this->password === '') {
            throw new \InvalidArgumentException(
                'Username and Password must be provided or defined as environment variables '
                . static::$ENV_USERNAME .' & ' . static::$ENV_PASSWORD
            );
        }

        // if its not injected, we instantiate directly
        if ($factory === null) {
            $factory = new GenericFactory();
        }

        $this->factory = $factory;
    }

    private static $cached_accessible = [
        'outbound' => 'Interfax\Outbound',
        'inbound' => 'Interfax\Inbound',
        'documents' => 'Interfax\Documents'
    ];

    /**
     * Simplifies the route to accessing specific 'route' classes for the client
     *
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        if (in_array($name, array_keys(static::$cached_accessible))) {
            if (!$this->$name) {
                $this->$name = $this->factory->instantiateClass(static::$cached_accessible[$name], [$this]);
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
            $config = ['base_uri' => $this->base_uri ?: static::$DEFAULT_BASE_URI];
            if ($this->debug) {
                $config['debug'] = true;
            }
            $this->http = $this->factory->instantiateClass(
                'GuzzleHttp\Client',
                [$config]
            );
        }

        return $this->http;
    }

    /**
     * @return string
     */
    public function getUserAgent()
    {
        return 'InterFAX PHP ' . static::VERSION;
    }

    /**
     * @return array
     */
    protected function getBaseRequestParams()
    {
        return [
            'auth' => [$this->username, $this->password],
            'headers' => [
                'User-Agent' => $this->getUserAgent()
            ],
            'http_errors' => false
        ];
    }

    /**
     * @param array $params
     * @return array
     */
    protected function getCompleteRequestParams($params = [])
    {
        $complete = array_merge_recursive($this->getBaseRequestParams(), $params);

        if (array_key_exists('query', $complete)) {
            foreach ($complete['query'] as $k => $v) {
                if (is_bool($v)) {
                    $complete['query'][$k] = $v ? 'TRUE' : 'FALSE';
                }
            }
        }

        return $complete;
    }

    /**
     * POST request.
     *
     * @param $uri
     * @param array $params
     * @param $multipart
     * @return string|array
     * @throws RequestException
     */
    public function post($uri, $params = [], $multipart = [])
    {
        if ($multipart && count($multipart)) {
            $request_params = $this->getCompleteRequestParams(array_merge($params, ['multipart' => $multipart]));
        } else {
            $request_params = $this->getCompleteRequestParams($params);
        }
        
        try {
            return $this->parseResponse(
                $this->getHttpClient()->request('POST', $uri, $request_params)
            );
        } catch (\GuzzleHttp\Exception\RequestException $e) {
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
        $request_params = $this->getCompleteRequestParams($params);

        try {
            $response = $this->getHttpClient()->request('GET', $uri, $request_params);
            return $this->parseResponse($response);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw RequestException::create('Problem with GET request', $e);
        }
    }

    /**
     * @param $uri
     * @return int - status code of the API request
     *
     */
    public function delete($uri)
    {
        $params = $this->getCompleteRequestParams();
        try {
            $response = $this->getHttpClient()->request('DELETE', $uri, $params);
            return $response->getStatusCode();
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw RequestException::create('Problem with DELETE request', $e);
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
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            if ($location = $response->getHeaderLine('Location')) {
                return $location;
            } elseif ($response->getHeaderLine('Content-Type') === 'text/json') {
                return json_decode((string) $response->getBody(), true);
            } else {
                return $response->getBody();
            }
        } else {
            throw RequestException::createForResponse('Unsuccessful request', $response);
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

    public function getBaseUri()
    {
        return $this->getHttpClient()->getConfig('base_uri');
    }
}
