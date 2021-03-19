<?php
/**
 * Interfax
 *
 * (C) InterFAX, 2016
 *
 * @package   interfax/interfax
 * @author    Interfax <dev@interfax.net>
 * @author    Mike Smith <mike.smith@camc-ltd.co.uk>
 * @copyright Copyright (c) 2016, InterFAX
 * @license   MIT
 */

require_once __DIR__ . '/../vendor/autoload.php';

if (!class_exists('\PHPUnit_Framework_TestCase')
    && class_exists('\PHPUnit\Framework\TestCase')
) {
    class_alias('\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase');
}

