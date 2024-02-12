<?php
namespace ZfcUser\Factory\Form;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use ZfcUser\Form\ChangePassword;
use ZfcUser\Form\ChangePasswordFilter;
use ZfcUser\Options;

class ChangePasswordFormFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceManager)
    {
        /* @var $options Options\ModuleOptions */
        $options = $serviceManager->get('zfcuser_module_options');

        $inputFilter = new ChangePasswordFilter($options);

        $form = new ChangePassword(null, $options);
        $form->setInputFilter($inputFilter);

        return $form;
    }
}
