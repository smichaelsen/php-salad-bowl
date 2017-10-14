<?php

namespace Smichaelsen\SaladBowl;

use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Smichaelsen\SaladBowl\Domain\Factory\ConfigurationFactory;
use Smichaelsen\SaladBowl\Domain\Factory\EntityManagerFactory;
use Smichaelsen\SaladBowl\Domain\Factory\RequestFactory;
use Smichaelsen\SaladBowl\Plugin\PluginLoader;
use Zend\Diactoros\Response;
use Zend\Diactoros\Server;

class Bowl
{

    /**
     * @var EntityManager
     */
    protected $entityManager;

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
        if (!$configuration) {
            $configuration = ServiceContainer::getSingleton(ConfigurationFactory::class)->create();
        }
        if (!isset($configuration[$key])) {
            return $default;
        }
        return $configuration[$key];
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
