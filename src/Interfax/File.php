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

class File
{
    protected $headers = [];
    protected $mime_type;
    protected $name;
    protected $body;

    /**
     * File constructor.
     * @param $location
     * @param array $params
     * @throws \InvalidArgumentException
     */
    public function __construct($location, $params = [])
    {
        if (preg_match('/^https?:\/\//', $location)) {
            $this->initialiseFromUri($location);
        } else {
            $this->initialiseParams($params);
            $this->initialiseFromPath($location);
        }
    }

    /**
     * @param array $params
     */
    protected function initialiseParams($params = [])
    {
        if (array_key_exists('mime_type', $params)) {
            $this->setMimeType($params['mime_type']);
        }

        if (array_key_exists('name', $params)) {
            $this->name = $params['name'];
        }
    }

    /**
     * @param $mime_type
     */
    public function setMimeType($mime_type)
    {
        $this->headers = [
            'Content-Type' => $mime_type
        ];
        $this->mime_type = $mime_type;
    }

    /**
     * @param $location
     * @throws \InvalidArgumentException
     */
    protected function initialiseFromPath($location)
    {
        if (!file_exists($location)) {
            throw new \InvalidArgumentException(
                $location . ' not found. File must exists on filesystem to construct ' . __CLASS__
            );
        }

        if (!$this->mime_type) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $this->setMimeType(finfo_file($finfo, $location));
        }

        if (!$this->name) {
            $this->name = basename($location);
        }
        $this->body = fopen($location, 'r');
    }

    protected function initialiseFromUri($location)
    {
        $this->headers = [
            'Location' => $location
        ];
    }

    /**
     * @return string
     */
    public function getHeader()
    {
        return $this->headers;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }
}
