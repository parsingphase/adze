<?php
/**
 * Created by PhpStorm.
 * User: parsingphase
 * Date: 06/10/14
 * Time: 20:45
 */

namespace Phase\Adze;


use Phase\Adze\Application as AdzeApplication;
use Silex\Application as SilexApplication;
use Silex\ServiceProviderInterface;

class TwigExtensionsServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param SilexApplication $app An Application instance
     */
    public function register(SilexApplication $app)
    {
        $app = AdzeApplication::assertAdzeApplication($app);

        $extensionProvider = $this;

        $app['twig'] = $app->share(
            $app->extend(
                'twig',
                function (\Twig_Environment $twig, $app) use ($extensionProvider) {
                    // add custom globals, filters, tags, ...
                    $filter = new \Twig_SimpleFilter(
                        'truncateAtSentence',
                        array($extensionProvider, 'truncateAtSentence')
                    );
                    $twig->addFilter($filter);
                    return $twig;
                }
            )
        );
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     * @param SilexApplication $app
     * @throws Exception\UnpromotedApplicationException
     */
    public function boot(SilexApplication $app)
    {
    }

    /**
     * Crop text to the specified character length, pulling back to last full stop
     *
     * @param string $string
     * @param int $charLength
     * @return string
     */
    public function truncateAtSentence($string, $charLength)
    {
        $truncated = substr($string, 0, $charLength);
        $lastPoint = strrpos($truncated, '.');
        if ($lastPoint !== false) {
            $truncated = substr($truncated, 0, $lastPoint + 1);
        }

        if (strlen($truncated) < strlen($string)) {
            $truncated .= ' …';
        }

        return $truncated;
    }
}
