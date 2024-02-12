<?php
namespace ZfcUser\Factory\View\Helper;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\HelperPluginManager;
use ZfcUser\Form;
use ZfcUser\Options;
use ZfcUser\View\Helper\ZfcUserLoginWidget;

class LoginWidgetFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $pluginManager)
    {
        /* @var $pluginManager HelperPluginManager */
        $serviceManager = $pluginManager->getServiceLocator();

        /* @var $options Options\ModuleOptions */
        $options = $serviceManager->get('zfcuser_module_options');
        $viewTemplate = $options->getUserLoginWidgetViewTemplate();

        /* @var $loginForm Form\Login */
        $loginForm = $serviceManager->get('zfcuser_login_form');

        $viewHelper = new ZfcUserLoginWidget;
        $viewHelper
            ->setViewTemplate($viewTemplate)
            ->setLoginForm($loginForm)
        ;

        return $viewHelper;
    }
}
