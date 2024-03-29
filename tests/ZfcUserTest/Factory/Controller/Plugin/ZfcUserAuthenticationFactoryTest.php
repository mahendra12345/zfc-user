<?php
namespace ZfcUserTest\Factory\Controller\Plugin;

use Laminas\ServiceManager\ServiceManager;
use ZfcUser\Factory\Controller\Plugin\ZfcUserAuthenticationFactory;

class ZfcUserAuthenticationFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testFactory()
    {
        $serviceManager = new ServiceManager;

        $serviceManager->setService('zfcuser_auth_service', new \Laminas\Authentication\AuthenticationService);
        $serviceManager->setService('ZfcUser\Authentication\Adapter\AdapterChain', new \ZfcUser\Authentication\Adapter\AdapterChain);

        $plugins = $this->getMock('Laminas\ServiceManager\AbstractPluginManager');
        $plugins->expects($this->any())
            ->method('getServiceLocator')
            ->will($this->returnValue($serviceManager));

        $factory = new ZfcUserAuthenticationFactory;
        $this->assertInstanceOf('ZfcUser\Controller\Plugin\ZfcUserAuthentication', $factory->createService($plugins));
    }
}
