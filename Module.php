<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */

namespace ZfcUser;

use Laminas\ModuleManager\Feature\AutoloaderProviderInterface;

class Module implements
    AutoloaderProviderInterface
{
    public function getAutoloaderConfig()
    {
        return array(
            'Laminas\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getControllerPluginConfig()
    {
        return array(
            'factories' => array(
                'zfcUserAuthentication' => 'ZfcUser\Factory\Controller\Plugin\ZfcUserAuthenticationFactory',
            ),
        );
    }

    public function getViewHelperConfig()
    {
        return array(
            'factories' => array(
                'zfcUserDisplayName'    => 'ZfcUser\Factory\View\Helper\DisplayNameFactory',
                'zfcUserIdentity'       => 'ZfcUser\Factory\View\Helper\IdentityFactory',
                'zfcUserLoginWidget'    => 'ZfcUser\Factory\View\Helper\LoginWidgetFactory',
            ),
        );
    }

    public function getServiceConfig()
    {
        return array(
            'invokables' => array(
                'ZfcUser\Authentication\Adapter\Db' => 'ZfcUser\Authentication\Adapter\Db',
                'ZfcUser\Authentication\Storage\Db' => 'ZfcUser\Authentication\Storage\Db',
                'ZfcUser\Form\Login'                => 'ZfcUser\Form\Login',
                'zfcuser_user_service'              => 'ZfcUser\Service\User',
            ),
            'factories' => array(
                'zfcuser_module_options'                        => 'ZfcUser\Factory\ModuleOptionsFactory',
                'zfcuser_auth_service'                          => 'ZfcUser\Factory\AuthenticationServiceFactory',
                'ZfcUser\Authentication\Adapter\AdapterChain'   => 'ZfcUser\Authentication\Adapter\AdapterChainServiceFactory',
                'zfcuser_login_form'                            => 'ZfcUser\Factory\Form\LoginFormFactory',
                'zfcuser_register_form'                         => 'ZfcUser\Factory\Form\RegisterFormFactory',
                'zfcuser_user_mapper'                           => 'ZfcUser\Factory\UserMapperFactory',
                'zfcuser_user_hydrator'                         => 'ZfcUser\Factory\Mapper\UserHydratorFactory',
            ),
            'aliases' => array(
                'zfcuser_register_form_hydrator' => 'zfcuser_user_hydrator'
            ),
        );
    }
}
