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
    protected $header;
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
        if (!file_exists($location)) {
            throw new \InvalidArgumentException($location . ' not found. File must exists on filesystem to construct ' . __CLASS__);
        }

        if (array_key_exists('name', $params)) {
            $this->name = $params['name'];
        }

        $this->initialiseFromPath($location);
    }

    /**
     * @param $location
     */
    protected function initialiseFromPath($location)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $this->header = 'Content-Type: ' . finfo_file($finfo, $location);
        if (!$this->name) {
            $this->name = basename($location);
        }
        $this->body = file_get_contents($location);
    }

    /**
     * @return string
     */
    public function getHeader()
    {
        return $this->header;
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