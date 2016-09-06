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

class Client
{
    protected static $ENV_USERNAME = 'INTERFAX_USERNAME';
    protected static $ENV_PASSWORD = 'INTERFAX_PASSWORD';

    public $username;
    public $password;

    public function __construct($username = null, $password = null)
    {
        if ($username === null) {
            $username = getenv(static::$ENV_USERNAME);
        }
        if ($password === null) {
            $password = getenv(static::$ENV_PASSWORD);
        }

        $this->username = $username;
        $this->password = $password;
        if (strlen($this->username) === 0 || strlen($this->password) === 0) {
            throw new \InvalidArgumentException('Username and Password must be provided or defined as environment variables ' . static::$ENV_USERNAME .' & ' . static::$ENV_PASSWORD);
        }
    }

}