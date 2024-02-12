<?php
namespace ZfcUser\Factory;

use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Adapter;
use Laminas\Authentication\Storage;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class AuthenticationServiceFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /* @var $authStorage Storage\StorageInterface */
        $authStorage = $serviceLocator->get('ZfcUser\Authentication\Storage\Db');

        /* @var $authAdapter Adapter\AdapterInterface */
        $authAdapter = $serviceLocator->get('ZfcUser\Authentication\Adapter\AdapterChain');

        return new AuthenticationService(
            $authStorage,
            $authAdapter
        );
    }
}
