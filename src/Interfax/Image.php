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

namespace Interfax;

use GuzzleHttp\Psr7\Stream;
use RuntimeException;

/**
 * Class Image
 *
 * Simple class to accept a response stream and allow content to be saved to a file
 *
 * @package Interfax
 */
class Image
{
    /**
     * @var Stream
     */
    private $stream;

    public function __construct(Stream $stream)
    {
        $this->stream = $stream;
    }

    /**
     * Note a return of false does not indicate that a file has not been created or written to,
     * just that it failed at some point.
     *
     * @param $path
     * @return bool
     * @throws \RuntimeException
     */
    public function save($path)
    {
        $handle = fopen($path, 'w');

        if (!$handle) {
            throw new \RuntimeException("Could not open {$path} for saving");
        }

        try {
            while (!$this->stream->eof()) {
                // TODO consider chunking size configuration
                fwrite($handle, $this->stream->read(1024 * 1024));
            }
        } catch (\RuntimeException $e) {
            // try to at least tidy up the resource
            fclose($handle);
            throw $e;
        }

        return fclose($handle);
    }
}
