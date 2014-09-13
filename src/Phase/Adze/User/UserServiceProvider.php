<?php
/**
 * Created by PhpStorm.
 * User: parsingphase
 * Date: 12/09/14
 * Time: 13:37
 */

namespace Phase\Adze\User;

use Phase\Adze\Application as AdzeApplication;
use Silex\Application as SilexApplication;
use Silex\ControllerCollection;
use Silex\ServiceControllerResolver;
use SimpleUser\UserController;
use SimpleUser\UserServiceProvider as SimpleUserServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Slight reduction & customisation of functionality of the excellent jasongrimes/silex-simpleuser to meet our needs
 * @package Phase\Adze\User
 */
class UserServiceProvider extends SimpleUserServiceProvider
{
    /**
     * Registers services on the given app.
     *
     * Used here to customise the templates used by the UserController
     *
     * @param SilexApplication $app An Application instance
     */
    public function register(SilexApplication $app)
    {
        parent::register($app);

        // Use Adze layout template in preference
        $app->extend(
            'user.controller',
            function (UserController $userController) {
                $userController->setLoginTemplate('@user/login.html.twig'); // use adze naming convention
                $userController->setLayoutTemplate('layout.html.twig');
                return $userController;
            }
        );
    }

    /**
     * Bootstraps the application. Here, adds our own local user template path
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     */
    public function boot(SilexApplication $app)
    {
        $moduleRoot = dirname(dirname(dirname(dirname(__DIR__)))); // really need neater ways of getting path roots...
        parent::boot($app);
        $app = AdzeApplication::assertAdzeApplication($app);
        //prepend our own template path for user templates
        $app->getTwigFilesystemLoader()->prependPath($moduleRoot . '/templates/user', 'user');
    }


    /**
     * Returns routes to connect to the given application.
     * Customised to disable some routes if $app['user.allowUserAdmin'] is unset or falsy
     *
     * @param SilexApplication $app An Application instance
     *
     * @return ControllerCollection A ControllerCollection instance
     * @throws \LogicException if ServiceController service provider is not registered.
     */
    public function connect(SilexApplication $app)
    {
        if (!$app['resolver'] instanceof ServiceControllerResolver) {
            // using RuntimeException crashes PHP?!
            throw new \LogicException('You must enable the ServiceController service provider to be able to use these routes.');
        }

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $allowUserAdmin = isset($app['user.allowUserAdmin']) && $app['user.allowUserAdmin'];

        if ($allowUserAdmin) {
            $controllers->get('/', 'user.controller:viewSelfAction')
                ->bind('user')
                ->before(
                    function (Request $request) use ($app) {
                        // Require login. This should never actually cause access to be denied,
                        // but it causes a login form to be rendered if the viewer is not logged in.
                        if (!$app['user']) {
                            throw new AccessDeniedException();
                        }
                    }
                );

            $controllers->get('/{id}', 'user.controller:viewAction')
                ->bind('user.view')
                ->assert('id', '\d+');
        }

        $controllers->method('GET|POST')->match('/{id}/edit', 'user.controller:editAction')
            ->bind('user.edit')
            ->before(
                function (Request $request) use ($app) {
                    if (!$app['security']->isGranted('EDIT_USER_ID', $request->get('id'))) {
                        throw new AccessDeniedException();
                    }
                }
            );

        if ($allowUserAdmin) {
            $controllers->get('/list', 'user.controller:listAction')
                ->bind('user.list');

            $controllers->method('GET|POST')->match('/register', 'user.controller:registerAction')
                ->bind('user.register');
        }

        $controllers->get('/login', 'user.controller:loginAction')
            ->bind('user.login');

        // login_check and logout are dummy routes so we can use the names.
        // The security provider should intercept these, so no controller is needed.
        $controllers->method('GET|POST')->match(
            '/login_check',
            function () {
            }
        )
            ->bind('user.login_check');
        $controllers->get(
            '/logout',
            function () {
            }
        )
            ->bind('user.logout');

        return $controllers;
    }
}
