<?php
namespace ZfcUser\Factory\Mapper;

use Laminas\Crypt\Password\Bcrypt;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use ZfcUser\Mapper;

class UserHydratorFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $options = $serviceLocator->get('zfcuser_module_options');
        $crypto  = new Bcrypt;
        $crypto->setCost($options->getPasswordCost());
        return new Mapper\UserHydrator($crypto);
    }
}
