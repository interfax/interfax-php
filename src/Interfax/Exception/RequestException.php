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

namespace Interfax\Exception;

use \GuzzleHttp\Exception\RequestException as GuzzleException;
use \GuzzleHttp\Psr7\Response;

/**
 * Class RequestException
 * @package Interfax\Exception
 */
class RequestException extends \RuntimeException
{
    protected $http_status;
    protected $request_exception;
    public static $UNEXPECTED_RESPONSE_CODE = -9999;

    /**
     * RequestException constructor.
     * @param string $message
     * @param int $code
     * @param int $http_status
     * @param GuzzleException $request_exception
     * @param \Exception|null $previous
     */
    public function __construct(
        $message,
        $code,
        $http_status,
        GuzzleException $request_exception = null,
        \Exception $previous = null
    ) {
        $this->http_status = $http_status;
        $this->request_exception = $request_exception;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @param $message
     * @param \GuzzleHttp\Exception\RequestException $exception
     * @return RequestException
     */
    public static function create($message, GuzzleException $exception)
    {
        $code = static::$UNEXPECTED_RESPONSE_CODE;

        $http_status = null;
        if ($exception->hasResponse()) {
            $response = $exception->getResponse();
            $http_status = $response->getStatusCode();
            if ($response->hasHeader('Content-Type') && $response->getHeaderLine('Content-Type') === 'text/json') {
                $json = json_decode((string)$response->getBody(), true);
                if ($json !== null) {
                    if (array_key_exists('code', $json)) {
                        $code = $json['code'];
                    }
                    if (array_key_exists('message', $json)) {
                        $message .= ': ' . $json['message'];
                    }
                }
            } else {
                $message .= ': ' . ($response->getBody() ?: 'no further information provided.');
            }
        }

        return new self($message, $code, $http_status, $exception);
    }

    public static function createForResponse($message, Response $response, $code=null)
    {
        if (is_null($code)) {
            $code = static::$UNEXPECTED_RESPONSE_CODE;
        }

        if ($response->hasHeader('Content-Type') && $response->getHeaderLine('Content-Type') === 'text/json') {
            $json = json_decode((string)$response->getBody(), true);
            if ($json !== null) {
                if (array_key_exists('code', $json)) {
                    $code = $json['code'];
                }
                if (array_key_exists('message', $json)) {
                    $message .= ': ' . $json['message'];
                }
            }
        } else {
            $message .= ': ' . ($response->getBody() ?: 'no response information provided.');
        }

        return new self($message, $code, $response->getStatusCode());
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->http_status;
    }

    /**
     * @return \GuzzleHttp\Exception\RequestException
     */
    public function getWrappedException()
    {
        return $this->request_exception;
    }
}
