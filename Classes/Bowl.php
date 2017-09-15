<?php

namespace Smichaelsen\SaladBowl;

use Doctrine\ORM\EntityManager;
use Noodlehaus\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Smichaelsen\SaladBowl\Domain\Factory\EntityManagerFactory;
use Smichaelsen\SaladBowl\Domain\Factory\RequestFactory;
use Smichaelsen\SaladBowl\Plugin\PluginLoader;
use Zend\Diactoros\Response;
use Zend\Diactoros\Server;

class Bowl
{

    /**
     * @var Config
     */
    protected $configuration;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var \Twig_Environment
     */
    protected $twigEnvironment;

    public function __construct(string $rootPath)
    {
        set_exception_handler(function (\Throwable $exception) {
            echo 'Oh no. What a mess! An error occured. <pre>';
            var_dump($exception);
            die();
        });
        define('BOWL_ROOT_PATH', $rootPath);
        ServiceContainer::getSingleton(PluginLoader::class)();
    }

    public static function getConfiguration($key, $default = null)
    {
        static $configuration;
        if (!$configuration instanceof Config) {
            $paths = [BOWL_ROOT_PATH . '/config/config.json'];
            if (is_readable(BOWL_ROOT_PATH . '/config/config.local.json')) {
                $paths[] = BOWL_ROOT_PATH . '/config/config.local.json';
            }
            $configuration = Config::load($paths);
        }
        if (!$configuration->has($key)) {
            return $default;
        }
        return $configuration->get($key);
    }

    public function getEntityManager(): EntityManager
    {
        if (!$this->entityManager instanceof EntityManager) {
            $this->entityManager = ServiceContainer::getSingleton(EntityManagerFactory::class)->create();
        }
        return $this->entityManager;
    }

    public function getServer(): Server
    {
        return new Server(
            function (ServerRequestInterface $request, ResponseInterface $response) {
                return ServiceContainer::getSingleton(
                    RequestHandler::class,
                    $this
                )->handle($request, $response);
            },
            RequestFactory::create(),
            ServiceContainer::getSingleton(Response::class)
        );
    }
}
