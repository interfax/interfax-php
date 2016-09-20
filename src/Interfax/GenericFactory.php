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

class GenericFactory
{
    public function instantiateClass($classname, $args = [])
    {
        if (count($args)) {
            $reflect = new \ReflectionClass($classname);

            return $reflect->newInstanceArgs($args);
        } else {
            return new $classname();
        }
    }
}
