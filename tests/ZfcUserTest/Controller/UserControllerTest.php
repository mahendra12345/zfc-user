<?php

namespace ZfcUserTest\Controller;

use ZfcUser\Controller\UserController as Controller;
use Laminas\Http\Response;
use Laminas\Stdlib\Parameters;
use Laminas\ServiceManager\ServiceLocatorInterface;
use ZfcUser\Service\User as UserService;
use Laminas\Form\Form;
use ZfcUser\Options\ModuleOptions;
use ZfcUser\Entity\User as UserIdentity;

class UserControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Controller $controller
     */
    protected $controller;

    protected $pluginManager;

    public $pluginManagerPlugins = array();

    protected $zfcUserAuthenticationPlugin;

    protected $options;

    protected $userService;

    public function setUp()
    {
        $this->userService = $this->getMock('ZfcUser\Service\User');

        $this->options = $this->getMock('ZfcUser\Options\ModuleOptions');

        $this->registerForm = $this->getMockBuilder('ZfcUser\Form\Register')
            ->disableOriginalConstructor()
            ->getMock();

        $this->loginForm = $this->getMockBuilder('ZfcUser\Form\Login')
            ->disableOriginalConstructor()
            ->getMock();

        $controller = new Controller($this->userService, $this->options, $this->registerForm, $this->loginForm);
        $this->controller = $controller;

        $this->zfcUserAuthenticationPlugin = $this->getMock('ZfcUser\Controller\Plugin\ZfcUserAuthentication');

        $pluginManager = $this->getMock('Laminas\Mvc\Controller\PluginManager', array('get'));

        $pluginManager->expects($this->any())
                      ->method('get')
                      ->will($this->returnCallback(array($this, 'helperMockCallbackPluginManagerGet')));

        $this->pluginManager = $pluginManager;

        $controller->setPluginManager($pluginManager);
    }

    public function setUpZfcUserAuthenticationPlugin($option)
    {
        if (array_key_exists('hasIdentity', $option)) {
            $return = (is_callable($option['hasIdentity']))
                ? $this->returnCallback($option['hasIdentity'])
                : $this->returnValue($option['hasIdentity']);
            $this->zfcUserAuthenticationPlugin->expects($this->any())
                 ->method('hasIdentity')
                 ->will($return);
        }

        if (array_key_exists('getAuthAdapter', $option)) {
            $return = (is_callable($option['getAuthAdapter']))
                ? $this->returnCallback($option['getAuthAdapter'])
                : $this->returnValue($option['getAuthAdapter']);

            $this->zfcUserAuthenticationPlugin->expects($this->any())
                 ->method('getAuthAdapter')
                 ->will($return);
        }

        if (array_key_exists('getAuthService', $option)) {
            $return = (is_callable($option['getAuthService']))
                ? $this->returnCallback($option['getAuthService'])
                : $this->returnValue($option['getAuthService']);

            $this->zfcUserAuthenticationPlugin->expects($this->any())
                 ->method('getAuthService')
                 ->will($return);
        }

        $this->pluginManagerPlugins['zfcUserAuthentication'] = $this->zfcUserAuthenticationPlugin;

        return $this->zfcUserAuthenticationPlugin;
    }

    /**
     * @dataProvider providerTestActionControllHasIdentity
     */
    public function testActionControllHasIdentity($methodeName, $hasIdentity, $redirectRoute, $optionGetter)
    {
        $controller = $this->controller;
        $redirectRoute = $redirectRoute ?: $controller::ROUTE_LOGIN;

        $this->setUpZfcUserAuthenticationPlugin(array(
            'hasIdentity'=>$hasIdentity
        ));

        $response = new Response();

        if ($optionGetter) {
            $this->options->expects($this->once())
                          ->method($optionGetter)
                          ->will($this->returnValue($redirectRoute));
        }

        $redirect = $this->getMock('Laminas\Mvc\Controller\Plugin\Redirect');
        $redirect->expects($this->once())
                 ->method('toRoute')
                 ->with($redirectRoute)
                 ->will($this->returnValue($response));

        $this->pluginManagerPlugins['redirect']= $redirect;

        $result = call_user_func(array($controller, $methodeName));

        $this->assertInstanceOf('Laminas\Http\Response', $result);
        $this->assertSame($response, $result);
    }

    /**
     * @depend testActionControllHasIdentity
     */
    public function testIndexActionLoggedIn()
    {
        $controller = $this->controller;
        $this->setUpZfcUserAuthenticationPlugin(array(
            'hasIdentity'=>true
        ));

        $result = $controller->indexAction();

        $this->assertInstanceOf('Laminas\View\Model\ViewModel', $result);
    }


    /**
     * @dataProvider providerTrueOrFalseX2
     * @depend testActionControllHasIdentity
     */
    public function testLoginActionValidFormRedirectFalse($isValid, $wantRedirect)
    {
        $controller = $this->controller;
        $redirectUrl = 'localhost/redirect1';

        $plugin = $this->setUpZfcUserAuthenticationPlugin(array(
            'hasIdentity'=>false
        ));

        $flashMessenger = $this->getMock(
            'Laminas\Mvc\Controller\Plugin\FlashMessenger'
        );
        $this->pluginManagerPlugins['flashMessenger']= $flashMessenger;

        $flashMessenger->expects($this->any())
                       ->method('setNamespace')
                       ->with('zfcuser-login-form')
                       ->will($this->returnSelf());

        $flashMessenger->expects($this->once())
                       ->method('getMessages')
                       ->will($this->returnValue(array()));

        $flashMessenger->expects($this->any())
                       ->method('addMessage')
                       ->will($this->returnSelf());

        $postArray = array('some', 'data');
        $request = $this->getMock('Laminas\Http\Request');
        $request->expects($this->any())
                ->method('isPost')
                ->will($this->returnValue(true));
        $request->expects($this->any())
                ->method('getPost')
                ->will($this->returnValue($postArray));

        $this->helperMakePropertyAccessable($controller, 'request', $request);

        $form = $this->loginForm;

        $form->expects($this->any())
             ->method('isValid')
             ->will($this->returnValue((bool) $isValid));


        $this->options->expects($this->any())
                      ->method('getUseRedirectParameterIfPresent')
                      ->will($this->returnValue((bool) $wantRedirect));
        if ($wantRedirect) {
            $params = new Parameters();
            $params->set('redirect', $redirectUrl);

            $request->expects($this->any())
                    ->method('getQuery')
                    ->will($this->returnValue($params));
        }

        if ($isValid) {
            $adapter = $this->getMock('ZfcUser\Authentication\Adapter\AdapterChain');
            $adapter->expects($this->once())
                    ->method('resetAdapters');

            $service = $this->getMock('Laminas\Authentication\AuthenticationService');
            $service->expects($this->once())
                    ->method('clearIdentity');

            $plugin = $this->setUpZfcUserAuthenticationPlugin(array(
                'getAuthAdapter'=>$adapter,
                'getAuthService'=>$service
            ));

            $form->expects($this->once())
                 ->method('setData')
                 ->with($postArray);

            $expectedResult = new \stdClass();

            $forwardPlugin = $this->getMockBuilder('Laminas\Mvc\Controller\Plugin\Forward')
                                  ->disableOriginalConstructor()
                                  ->getMock();
            $forwardPlugin->expects($this->once())
                          ->method('dispatch')
                          ->with($controller::CONTROLLER_NAME, array('action' => 'authenticate'))
                          ->will($this->returnValue($expectedResult));

            $this->pluginManagerPlugins['forward']= $forwardPlugin;

        } else {
            $response = new Response();

            $redirectQuery = $wantRedirect ? '?redirect='. rawurlencode($redirectUrl) : '';
            $route_url = "/user/login";


            $redirect = $this->getMock('Laminas\Mvc\Controller\Plugin\Redirect', array('toUrl'));
            $redirect->expects($this->any())
                     ->method('toUrl')
                     ->with($route_url . $redirectQuery)
                     ->will($this->returnCallback(function ($url) use (&$response) {
                         $response->getHeaders()->addHeaderLine('Location', $url);
                         $response->setStatusCode(302);

                         return $response;
                     }));

            $this->pluginManagerPlugins['redirect']= $redirect;


            $response = new Response();
            $url = $this->getMock('Laminas\Mvc\Controller\Plugin\Url', array('fromRoute'));
            $url->expects($this->once())
                     ->method('fromRoute')
                     ->with($controller::ROUTE_LOGIN)
                     ->will($this->returnValue($route_url));

            $this->pluginManagerPlugins['url']= $url;
            $TEST = true;
        }

        $result = $controller->loginAction();

        if ($isValid) {
            $this->assertSame($expectedResult, $result);
        } else {
            $this->assertInstanceOf('Laminas\Http\Response', $result);
            $this->assertEquals($response, $result);
            $this->assertEquals($route_url . $redirectQuery, $result->getHeaders()->get('Location')->getFieldValue());
        }
    }

    /**
     * @dataProvider providerTrueOrFalse
     * @depend testActionControllHasIdentity
     */
    public function testLoginActionIsNotPost($redirect)
    {
        $plugin = $this->setUpZfcUserAuthenticationPlugin(array(
            'hasIdentity'=>false
        ));

        $flashMessenger = $this->getMock('Laminas\Mvc\Controller\Plugin\FlashMessenger');
        $flashMessenger->expects($this->once())
                       ->method('setNamespace')
                       ->with('zfcuser-login-form')
                       ->will($this->returnSelf());

        $this->pluginManagerPlugins['flashMessenger']= $flashMessenger;

        $request = $this->getMock('Laminas\Http\Request');
        $request->expects($this->once())
                ->method('isPost')
                ->will($this->returnValue(false));

        $form = $this->loginForm;
        $form->expects($this->never())
             ->method('isValid');



        $this->options->expects($this->any())
                      ->method('getUseRedirectParameterIfPresent')
                      ->will($this->returnValue((bool) $redirect));
        if ($redirect) {
            $params = new Parameters();
            $params->set('redirect', 'http://localhost/');

            $request->expects($this->any())
                    ->method('getQuery')
                    ->will($this->returnValue($params));
        }

        $this->helperMakePropertyAccessable($this->controller, 'request', $request);

        $result = $this->controller->loginAction();

        $this->assertArrayHasKey('loginForm', $result);
        $this->assertArrayHasKey('redirect', $result);
        $this->assertArrayHasKey('enableRegistration', $result);

        $this->assertInstanceOf('ZfcUser\Form\Login', $result['loginForm']);
        $this->assertSame($form, $result['loginForm']);

        if ($redirect) {
            $this->assertEquals('http://localhost/', $result['redirect']);
        } else {
            $this->assertFalse($result['redirect']);
        }

        $this->assertEquals($this->options->getEnableRegistration(), $result['enableRegistration']);
    }


    /**
     * @dataProvider providerRedirectPostQueryMatrix
     * @depend testActionControllHasIdentity
     */
    public function testLogoutAction($withRedirect, $post, $query)
    {
        $controller = $this->controller;

        $adapter = $this->getMock('ZfcUser\Authentication\Adapter\AdapterChain');
        $adapter->expects($this->once())
                ->method('resetAdapters');

        $adapter->expects($this->once())
                ->method('logoutAdapters');

        $service = $this->getMock('Laminas\Authentication\AuthenticationService');
        $service->expects($this->once())
                ->method('clearIdentity');

        $this->setUpZfcUserAuthenticationPlugin(array(
            'getAuthAdapter'=>$adapter,
            'getAuthService'=>$service
        ));


        $params = $this->getMock('Laminas\Mvc\Controller\Plugin\Params');
        $params->expects($this->any())
               ->method('__invoke')
               ->will($this->returnSelf());
        $params->expects($this->once())
               ->method('fromPost')
               ->will($this->returnCallback(function ($key, $default) use ($post) {
                   return $post ?: $default;
               }));
        $params->expects($this->once())
               ->method('fromQuery')
               ->will($this->returnCallback(function ($key, $default) use ($query) {
                   return $query ?: $default;
               }));
        $this->pluginManagerPlugins['params'] = $params;

        $response = new Response();

        $redirect = $this->getMock('Laminas\Mvc\Controller\Plugin\Redirect');
        $redirect->expects($this->any())
                 ->method('toRoute')
                 ->will($this->returnValue($response));

        if ($withRedirect) {
            $expectedLocation = $post ?: $query ?: false;
            $this->options->expects($this->once())
                          ->method('getUseRedirectParameterIfPresent')
                          ->will($this->returnValue((bool) $withRedirect));
            $redirect->expects($this->any())
                     ->method('toRoute')
                     ->with($expectedLocation)
                     ->will($this->returnValue($response));
        } else {
            $expectedLocation = "/user/logout";
            $this->options->expects($this->once())
                          ->method('getLogoutRedirectRoute')
                          ->will($this->returnValue($expectedLocation));
            $redirect->expects($this->any())
                     ->method('toRoute')
                     ->with($expectedLocation)
                     ->will($this->returnValue($response));
        }

        $this->pluginManagerPlugins['redirect']= $redirect;

        $result = $controller->logoutAction();

        $this->assertInstanceOf('Laminas\Http\Response', $result);
        $this->assertSame($response, $result);
    }

    public function testLoginRedirectFailsWithUrl()
    {

    }

    /**
     * @dataProvider providerTestAuthenticateAction
     * @depend testActionControllHasIdentity
     */
    public function testAuthenticateAction($wantRedirect, $post, $query, $prepareResult = false, $authValid = false)
    {
        $controller = $this->controller;
        $response = new Response();
        $hasRedirect = !(is_null($query) && is_null($post));

        $params = $this->getMock('Laminas\Mvc\Controller\Plugin\Params');
        $params->expects($this->any())
               ->method('__invoke')
               ->will($this->returnSelf());
        $params->expects($this->once())
               ->method('fromPost')
               ->will($this->returnCallback(function ($key, $default) use ($post) {
                   return $post ?: $default;
               }));
        $params->expects($this->once())
               ->method('fromQuery')
               ->will($this->returnCallback(function ($key, $default) use ($query) {
                   return $query ?: $default;
               }));
        $this->pluginManagerPlugins['params'] = $params;


        $request = $this->getMock('Laminas\Http\Request');
        $this->helperMakePropertyAccessable($controller, 'request', $request);


        $adapter = $this->getMock('ZfcUser\Authentication\Adapter\AdapterChain');
        $adapter->expects($this->once())
                ->method('prepareForAuthentication')
                ->with($request)
                ->will($this->returnValue($prepareResult));

        $service = $this->getMock('Laminas\Authentication\AuthenticationService');


        $this->setUpZfcUserAuthenticationPlugin(array(
            'hasIdentity'=>false,
            'getAuthAdapter'=>$adapter,
            'getAuthService'=>$service
        ));

        if (is_bool($prepareResult)) {

            $authResult = $this->getMockBuilder('Laminas\Authentication\Result')
                               ->disableOriginalConstructor()
                               ->getMock();
            $authResult->expects($this->once())
                       ->method('isValid')
                       ->will($this->returnValue($authValid));

            $service->expects($this->once())
                    ->method('authenticate')
                    ->with($adapter)
                    ->will($this->returnValue($authResult));

            $redirect = $this->getMock('Laminas\Mvc\Controller\Plugin\Redirect');
            $this->pluginManagerPlugins['redirect'] = $redirect;

            if (!$authValid) {
                $flashMessenger = $this->getMock(
                    'Laminas\Mvc\Controller\Plugin\FlashMessenger'
                );
                $this->pluginManagerPlugins['flashMessenger']= $flashMessenger;

                $flashMessenger->expects($this->once())
                               ->method('setNamespace')
                               ->with('zfcuser-login-form')
                               ->will($this->returnSelf());

                $flashMessenger->expects($this->once())
                               ->method('addMessage');

                $adapter->expects($this->once())
                        ->method('resetAdapters');

                $redirectQuery = ($post ?: $query ?: false);
                $redirectQuery = $redirectQuery ? '?redirect=' . rawurlencode($redirectQuery) : '';

                $redirect->expects($this->once())
                         ->method('toUrl')
                         ->with('user/login' . $redirectQuery)
                         ->will($this->returnValue($response));

                $url = $this->getMock('Laminas\Mvc\Controller\Plugin\Url');
                $url->expects($this->once())
                    ->method('fromRoute')
                    ->with($controller::ROUTE_LOGIN)
                    ->will($this->returnValue('user/login'));
                $this->pluginManagerPlugins['url'] = $url;

            } elseif ($wantRedirect && $hasRedirect) {
                $redirect->expects($this->once())
                         ->method('toRoute')
                         ->with(($post ?: $query ?: false))
                         ->will($this->returnValue($response));
            } else {

                $redirect->expects($this->once())
                         ->method('toRoute')
                         ->with('zfcuser')
                         ->will($this->returnValue($response));

                $this->options->expects($this->once())
                              ->method('getLoginRedirectRoute')
                              ->will($this->returnValue('zfcuser'));
            }

            $this->options->expects($this->any())
                          ->method('getUseRedirectParameterIfPresent')
                          ->will($this->returnValue((bool) $wantRedirect));

        }

        $result = $controller->authenticateAction();


    }

    /**
     *
     * @depend testActionControllHasIdentity
     */
    public function testRegisterActionIsNotAllowed()
    {
        $controller = $this->controller;

        $this->setUpZfcUserAuthenticationPlugin(array(
            'hasIdentity'=>false
        ));

        $this->options->expects($this->once())
                      ->method('getEnableRegistration')
                      ->will($this->returnValue(false));

        $result = $controller->registerAction();

        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('enableRegistration', $result);
        $this->assertFalse($result['enableRegistration']);
    }

    /**
     *
     * @dataProvider providerTestRegisterAction
     * @depend testActionControllHasIdentity
     * @depend testRegisterActionIsNotAllowed
     */
    public function testRegisterAction($wantRedirect, $postRedirectGetReturn, $registerSuccess, $loginAfterSuccessWith)
    {
        $controller = $this->controller;
        $redirectUrl = 'localhost/redirect1';
        $route_url = '/user/register';
        $expectedResult = null;

        $this->setUpZfcUserAuthenticationPlugin(array(
            'hasIdentity' => false
        ));

        $this->options->expects($this->any())
            ->method('getEnableRegistration')
            ->will($this->returnValue(true));

        $request = $this->getMock('Laminas\Http\Request');
        $this->helperMakePropertyAccessable($controller, 'request', $request);

        $form = $this->registerForm;

        $this->options->expects($this->any())
            ->method('getUseRedirectParameterIfPresent')
            ->will($this->returnValue((bool)$wantRedirect));

        if ($wantRedirect) {
            $params = new Parameters();
            $params->set('redirect', $redirectUrl);

            $request->expects($this->any())
                ->method('getQuery')
                ->will($this->returnValue($params));
        }


        $url = $this->getMock('Laminas\Mvc\Controller\Plugin\Url');
        $url->expects($this->at(0))
            ->method('fromRoute')
            ->with($controller::ROUTE_REGISTER)
            ->will($this->returnValue($route_url));

        $this->pluginManagerPlugins['url'] = $url;

        $prg = $this->getMock('Laminas\Mvc\Controller\Plugin\PostRedirectGet');
        $this->pluginManagerPlugins['prg'] = $prg;

        $redirectQuery = $wantRedirect ? '?redirect=' . rawurlencode($redirectUrl) : '';
        $prg->expects($this->once())
            ->method('__invoke')
            ->with($route_url . $redirectQuery)
            ->will($this->returnValue($postRedirectGetReturn));

        if ($registerSuccess) {
            $user = new UserIdentity();
            $user->setEmail('zfc-user@trash-mail.com');
            $user->setUsername('zfc-user');

            $this->userService->expects($this->once())
                ->method('register')
                ->with($postRedirectGetReturn)
                ->will($this->returnValue($user));

            $this->userService->expects($this->any())
                ->method('getOptions')
                ->will($this->returnValue($this->options));

            $this->options->expects($this->once())
                ->method('getLoginAfterRegistration')
                ->will($this->returnValue(!empty($loginAfterSuccessWith)));

            if ($loginAfterSuccessWith) {
                $this->options->expects($this->once())
                    ->method('getAuthIdentityFields')
                    ->will($this->returnValue(array($loginAfterSuccessWith)));


                $expectedResult = new \stdClass();
                $forwardPlugin = $this->getMockBuilder('Laminas\Mvc\Controller\Plugin\Forward')
                    ->disableOriginalConstructor()
                    ->getMock();
                $forwardPlugin->expects($this->once())
                    ->method('dispatch')
                    ->with($controller::CONTROLLER_NAME, array('action' => 'authenticate'))
                    ->will($this->returnValue($expectedResult));

                $this->pluginManagerPlugins['forward'] = $forwardPlugin;
            } else {
                $response = new Response();
                $route_url = '/user/login';

                $redirectUrl = isset($postRedirectGetReturn['redirect'])
                    ? $postRedirectGetReturn['redirect']
                    : null;

                $redirectQuery = $redirectUrl ? '?redirect=' . rawurlencode($redirectUrl) : '';

                $redirect = $this->getMock('Laminas\Mvc\Controller\Plugin\Redirect');
                $redirect->expects($this->once())
                    ->method('toUrl')
                    ->with($route_url . $redirectQuery)
                    ->will($this->returnValue($response));

                $this->pluginManagerPlugins['redirect'] = $redirect;


                $url->expects($this->at(1))
                    ->method('fromRoute')
                    ->with($controller::ROUTE_LOGIN)
                    ->will($this->returnValue($route_url));
            }
        }


        /***********************************************
         * run
         */
        $result = $controller->registerAction();


        /***********************************************
         * assert
         */
        if ($postRedirectGetReturn instanceof Response) {
            $expectedResult = $postRedirectGetReturn;
        }
        if ($expectedResult) {
            $this->assertSame($expectedResult, $result);
            return;
        }


        if ($postRedirectGetReturn === false) {
            $expectedResult = array(
                'registerForm' => $form,
                'enableRegistration' => true,
                'redirect' => $wantRedirect ? $redirectUrl : false
            );
        } elseif ($registerSuccess === false) {
            $expectedResult = array(
                'registerForm' => $form,
                'enableRegistration' => true,
                'redirect' => isset($postRedirectGetReturn['redirect']) ? $postRedirectGetReturn['redirect'] : null
            );
        }

        if ($expectedResult) {
            $this->assertInternalType('array', $result);
            $this->assertArrayHasKey('registerForm', $result);
            $this->assertArrayHasKey('enableRegistration', $result);
            $this->assertArrayHasKey('redirect', $result);
            $this->assertEquals($expectedResult, $result);
        } else {
            $this->assertInstanceOf('Laminas\Http\Response', $result);
            $this->assertSame($response, $result);
        }
    }

    public function providerTrueOrFalse ()
    {
        return array(
            array(true),
            array(false),
        );
    }

    public function providerTrueOrFalseX2 ()
    {
        return array(
            array(true,true),
            array(true,false),
            array(false,true),
            array(false,false),
        );
    }

    public function providerTestAuthenticateAction ()
    {
        // $redirect, $post, $query, $prepareResult = false, $authValid = false
        return array(
            array(false, null,              null,              new Response(), false),
            array(false, null,              null,              false,          false),
            array(false, null,              null,              false,          true),
            array(false, 'localhost/test1', null,              false,          false),
            array(false, 'localhost/test1', null,              false,          true),
            array(false, 'localhost/test1', 'localhost/test2', false,          false),
            array(false, 'localhost/test1', 'localhost/test2', false,          true),
            array(false, null,              'localhost/test2', false,          false),
            array(false, null,              'localhost/test2', false,          true),

            array(true,  null,              null,              false,          false),
            array(true,  null,              null,              false,          true),
            array(true,  'localhost/test1', null,              false,          false),
            array(true,  'localhost/test1', null,              false,          true),
            array(true,  'localhost/test1', 'localhost/test2', false,          false),
            array(true,  'localhost/test1', 'localhost/test2', false,          true),
            array(true,  null,              'localhost/test2', false,          false),
            array(true,  null,              'localhost/test2', false,          true),
        );
    }

    public function providerRedirectPostQueryMatrix ()
    {
        return array(
            array(false, false, false),
            array(true, false, false),
            array(true, 'localhost/test1', false),
            array(true, 'localhost/test1', 'localhost/test2'),
            array(true, false,              'localhost/test2'),
        );
    }

    public function providerTestActionControllHasIdentity()
    {

        return array(
            //    $methodeName , $hasIdentity, $redirectRoute,           optionsGetterMethode
            array('indexAction',          false, Controller::ROUTE_LOGIN,  null),
            array('loginAction',          true,  'user/overview',          'getLoginRedirectRoute'),
            array('authenticateAction',   true,  'user/overview',          'getLoginRedirectRoute'),
            array('registerAction',       true,  'user/overview',          'getLoginRedirectRoute'),
        );
    }


    public function providerTestChangeAction()
    {
        return array(
            //    $status, $postRedirectGetReturn, $isValid, $changeSuccess
            array(false,   new Response(),  null,  null),
            array(true,    new Response(),  null,  null),

            array(false,   false,           null,  null),
            array(true,    false,           null,  null),

            array(false,   array("test"),   false,  null),
            array(true,    array("test"),   false,  null),

            array(false,   array("test"),   true, false),
            array(true,    array("test"),   true, false),

            array(false,   array("test"),   true, true),
            array(true,    array("test"),   true, true),

        );
    }


    public function providerTestRegisterAction()
    {
        $registerPost = array (
            'username'=>'zfc-user',
            'email'=>'zfc-user@trash-mail.com',
            'password'=>'secret'
        );
        $registerPostRedirect = array_merge($registerPost, array("redirect" => 'test'));


        return array(
            //    $status, $postRedirectGetReturn, $registerSuccess, $loginAfterSuccessWith
            array(false,   new Response(),  null,  null),
            array(true,    new Response(),  null,  null),

            array(false,   false,           null,  null),
            array(true,    false,           null,  null),

            array(false,   $registerPost,   false,  null),
            array(true,    $registerPost,   false,  null),
            array(false,   $registerPostRedirect,   false,  null),
            array(true,    $registerPostRedirect,   false,  null),

            array(false,   $registerPost,   true, 'email'),
            array(true,    $registerPost,   true, 'email'),
            array(false,   $registerPostRedirect,   true, 'email'),
            array(true,    $registerPostRedirect,   true, 'email'),

            array(false,   $registerPost,   true, 'username'),
            array(true,    $registerPost,   true, 'username'),
            array(false,   $registerPostRedirect,   true, 'username'),
            array(true,    $registerPostRedirect,   true, 'username'),

            array(false,   $registerPost,   true, null),
            array(true,    $registerPost,   true, null),
            array(false,   $registerPostRedirect,   true, null),
            array(true,    $registerPostRedirect,   true, null),

        );
    }


    /**
     *
     * @param mixed $objectOrClass
     * @param string $property
     * @param mixed $value = null
     * @return \ReflectionProperty
     */
    public function helperMakePropertyAccessable ($objectOrClass, $property, $value = null)
    {
        $reflectionProperty = new \ReflectionProperty($objectOrClass, $property);
        $reflectionProperty->setAccessible(true);

        if ($value !== null) {
            $reflectionProperty->setValue($objectOrClass, $value);
        }
        return $reflectionProperty;
    }

    public function helperMockCallbackPluginManagerGet($key)
    {
        if ($key=="flashMessenger" && !array_key_exists($key, $this->pluginManagerPlugins)) {
//             echo "\n\n";
//             echo '$key: ' . $key . "\n";
//             var_dump(array_key_exists($key, $this->pluginManagerPlugins), array_keys($this->pluginManagerPlugins));
//             exit;
        }
        return (array_key_exists($key, $this->pluginManagerPlugins))
            ? $this->pluginManagerPlugins[$key]
            : null;
    }
}
