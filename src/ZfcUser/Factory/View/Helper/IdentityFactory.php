<?php
namespace ZfcUser\Factory\View\Helper;

use Laminas\Authentication\AuthenticationService;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\HelperPluginManager;
use ZfcUser\View\Helper\ZfcUserIdentity;

class IdentityFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $pluginManager)
    {
        /* @var $pluginManager HelperPluginManager */
        $serviceManager = $pluginManager->getServiceLocator();

        /* @var $authService AuthenticationService */
        $authService = $serviceManager->get('zfcuser_auth_service');

        $viewHelper = new ZfcUserIdentity;
        $viewHelper->setAuthService($authService);

        return $viewHelper;
    }
}
