<?php
/**
 * Created by PhpStorm.
 * User: parsingphase
 * Date: 06/09/14
 * Time: 17:16
 */

namespace Phase\Adze;


use Doctrine\DBAL\Connection;
use Phase\Adze\Exception\UnpromotedApplicationException;
use Phase\Adze\User\UserServiceProvider;
use Psr\Log\LoggerInterface;
use Silex\Application as SilexApplication;
use Silex\ControllerCollection;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\RememberMeServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * Extended Silex Application with selected functionality enabled and with convenient accessor functions
 * @package Phase\Adze
 */
class Application extends SilexApplication
{
    use SilexApplication\TwigTrait;
    use SilexApplication\SecurityTrait;
    use SilexApplication\FormTrait;
    use SilexApplication\UrlGeneratorTrait;
    use SilexApplication\MonologTrait;

    /**
     * @var ResourcesControllerProvider
     * @todo Register in DI store if required
     */
    protected $resourceController;

    /**
     * Checking shim function to ensure that we're using a full Adze Application rather than a base Silex one
     *
     * Typically used where a standard Silex interface specifies a Silex\Application but we're relying on receiving
     * an Adze\Application
     *
     * @param SilexApplication $app
     * @return Application
     * @throws Exception\UnpromotedApplicationException
     */
    public static function assertAdzeApplication(SilexApplication $app)
    {
        if (!$app instanceof self) {
            throw new UnpromotedApplicationException();
        }
        return $app;
    }

    /**
     * Set application configuration values from an associative array
     *
     * @param $config
     * @return $this
     */
    public function loadConfig($config)
    {
        foreach ($config as $k => $v) {
            $this[$k] = $v;
        }
        return $this;
    }

    /**
     * Accessor for the Controller Factory, to help create new Controllers
     *
     * @return ControllerCollection
     */
    public function getControllerFactory()
    {
        return $this['controllers_factory'];
    }

    /**
     * Accessor for the ResourceController, to be able to add new resource directories
     *
     * @return ResourcesControllerProvider
     */
    public function getResourceController()
    {
        return $this->resourceController;
    }

    /**
     * Get the default DB connection for the app
     *
     * @return Connection
     */
    public function getDatabaseConnection()
    {
        return $this['db'];
    }

    /**
     * Set up the standard Providers that an Adze application expects to rely on
     *
     * @return $this
     */
    public function setupCoreProviders()
    {
        $this->register(new MonologServiceProvider());
        $this->register(
            new SecurityServiceProvider(),
            array(
                'security.firewalls' => array(
                    'secured_area' => array(
                        'pattern' => '^.*$',
                        'anonymous' => true,
                        'remember_me' => array(),
                        'form' => array(
                            'login_path' => '/user/login',
                            'check_path' => '/user/login_check',
                        ),
                        'logout' => array(
                            'logout_path' => '/user/logout',
                        ),
                        'users' => $this->share(
                            function ($app) {
                                return $app['user.manager'];
                            }
                        ),
                    ),
                ),
            )
        );

        // Notes from https://github.com/jasongrimes/silex-simpleuser
        // Note: As of this writing, RememberMeServiceProvider must be registered *after* SecurityServiceProvider or SecurityServiceProvider
        // throws 'InvalidArgumentException' with message 'Identifier "security.remember_me.service.secured_area" is not defined.'
        $this->register(new RememberMeServiceProvider());

        $this->register(new SessionServiceProvider());
        $this->register(new TranslationServiceProvider()); // required for default form views
        $this->register(new FormServiceProvider());

        $this->register(new DoctrineServiceProvider());

        $this->register(new UrlGeneratorServiceProvider());
        $this->register(new ValidatorServiceProvider());
        $this->register(new ServiceControllerServiceProvider());

        $this->register(
            new TwigServiceProvider(),
            ['twig.options' => array('cache' => $this['twig.cache.dir'])]
        );
        /*
        $this['twig'] = $this->share(
            $this->extend(
                'twig',
                function ($twig, $app) {
                    // add custom globals, filters, tags, ...

                    return $twig;
                }
            )
        );
        */

        $this->resourceController = new ResourcesControllerProvider();
        $this->mount('/resources', $this->resourceController);

        // Register the SimpleUser service provider.
        $this->register($u = new UserServiceProvider());

        // Optionally mount the SimpleUser controller provider.
        $this->mount('/user', $u);

        return $this;
    }

    /**
     * Set up a default error page & logging
     */
    public function setUpErrorHandling()
    {
        $app = $this;
        $this->error(
            function (\Exception $e, $code) use ($app) {
                $response = null;

                if (!$app['debug']) { // Keep default output if debug's on
                    switch ($code) {
                        case 404:
                            $message = 'The requested page could not be found.';
                            break;
                        case 403:
                            $message = 'The requested page is not available.';
                            break;
                        default:
                            $message = 'Sorry, an internal error occurred.';
                    }

                    if (!in_array($code, [403, 404])) {
                        // Don't log 403/404 errors, far too much noise
                        //TODO put more context in here
                        $logMessage = $e->getMessage();
                        if (!$logMessage) {
                            if ($e) {
                                $logMessage = 'Threw ' . get_class($e);
                            } else {
                                $logMessage = 'Error thrown without exception';
                            }
                        }
                        $this->getLogger()->error("ADZE: $code: " . $logMessage);
                    }

                    $response = $this->render('error.html.twig', ['code' => $code, 'message' => $message]);
                }
                return $response;
            }
        );
    }

    /**
     * Get the twig loader so that more template sources can be added
     * @deprecated Use getTwigFilesystemLoader
     *
     * @return \Twig_Loader_Chain
     */
    public function getTwigLoaderChain()
    {
        return $this['twig.loader'];
    }

    /**
     * Return the Twig Filesystem Loader so we can prepend/append template paths
     *
     * @return \Twig_Loader_Filesystem
     */
    public function getTwigFilesystemLoader()
    {
        return $this['twig.loader.filesystem'];
    }

    /**
     * Builds and returns the factory.
     *
     * @return FormFactory
     */
    public function getFormFactory()
    {
        return $this['form.factory'];
    }

    /**
     * Get the security context so we can check grants
     *
     * @return SecurityContext
     */
    public function getSecurityContext()
    {
        return $this['security'];
    }

    /**
     * Get the logger instance
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this['monolog'];
    }

    /**
     * Get the Session instance
     *
     * @return Session
     */
    public function getSession()
    {
        return $this['session'];
    }


    /**
     * Convenience method to show *something* at '/'
     *
     * Users may remove the index.php call to this fairly early in their site setup
     */
    public function setUpDefaultHomepage()
    {
        $app = $this;
        $app->get(
            '/',
            function (Request $request) use ($app) {
                $viewData = [
                    'user' => $app->user() ? (string)$app->user()->getName() : null,
                    'time' => new \DateTime(),
                    'error' => $app['security.last_error']($request),
                    'last_username' => $app->getSession()->get('_security.last_username'),
                ];

                return $app->render(
                    'homepage.html.twig',
                    $viewData
                );
            }
        );
    }

    /**
     * If you want '/' to map to a mounted controller, use this to set it up
     *
     * @param string $url Relative URL to map to, eg /blog. Must have a valid route.
     */
    public function setDefaultRouteByUrl($url)
    {
        $app = $this;
        $app->get(
            '/',
            function () use ($app, $url) {
                // redirect to /hello
                $subRequest = Request::create($url, 'GET');

                return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
            }
        );
    }
}
