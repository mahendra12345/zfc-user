<?php

namespace ZfcUser\Mapper;

use Laminas\Crypt\Password\PasswordInterface as ZendCryptPassword;
use Laminas\Stdlib\Hydrator\HydratorInterface as ZendHydrator;

interface HydratorInterface extends ZendHydrator
{
    /**
     * @return ZendCryptPassword
     */
    public function getCryptoService();
}
