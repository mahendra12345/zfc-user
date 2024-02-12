<?php
namespace ZfcUser\Factory\Form;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use ZfcUser\Form\Login;
use ZfcUser\Form\LoginFilter;
use ZfcUser\Options;

class LoginFormFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceManager)
    {
        /* @var $options Options\ModuleOptions */
        $options = $serviceManager->get('zfcuser_module_options');

        $inputFilter = new LoginFilter($options);

        $form = new Login(null, $options);
        $form->setInputFilter($inputFilter);

        return $form;
    }
}
