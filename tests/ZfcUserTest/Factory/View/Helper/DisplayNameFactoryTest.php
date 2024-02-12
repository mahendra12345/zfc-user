<?php
namespace ZfcUserTest\Factory\View\Helper;

use ZfcUser\Factory\View\Helper\DisplayNameFactory;
use Laminas\ServiceManager\ServiceManager;

class DisplayNameFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testFactory()
    {
        $serviceManager = new ServiceManager;
        $serviceManager->setService('zfcuser_auth_service', new \Laminas\Authentication\AuthenticationService);

        $factory = new DisplayNameFactory;

        $helpers = $this->getMock('Laminas\ServiceManager\AbstractPluginManager');
        $helpers->expects($this->any())
            ->method('getServiceLocator')
            ->will($this->returnValue($serviceManager));

        $this->assertInstanceOf('ZfcUser\View\Helper\ZfcUserDisplayName', $factory->createService($helpers));
    }
}
