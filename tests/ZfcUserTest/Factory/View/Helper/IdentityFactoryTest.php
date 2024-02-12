<?php
namespace ZfcUserTest\Factory\View\Helper;

use ZfcUser\Factory\View\Helper\IdentityFactory;
use Laminas\ServiceManager\ServiceManager;

class IdentityFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testFactory()
    {
        $serviceManager = new ServiceManager;
        $serviceManager->setService('zfcuser_auth_service', new \Laminas\Authentication\AuthenticationService);

        $factory = new IdentityFactory;

        $helpers = $this->getMock('Laminas\ServiceManager\AbstractPluginManager');
        $helpers->expects($this->any())
            ->method('getServiceLocator')
            ->will($this->returnValue($serviceManager));

        $this->assertInstanceOf('ZfcUser\View\Helper\ZfcUserIdentity', $factory->createService($helpers));
    }
}
