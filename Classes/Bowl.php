<?php
namespace Smichaelsen\SaladBowl;

use Aura\Router\Matcher;
use Aura\Router\RouterContainer;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Noodlehaus\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Server;
use Zend\Diactoros\ServerRequestFactory;

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
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * @var Matcher
     */
    protected $routeMatcher;

    /**
     * @var string
     */
    protected $rootPath;

    /**
     * @var \Twig_Environment
     */
    protected $twigEnvironment;

    /**
     * @param string $rootPath
     */
    public function __construct($rootPath)
    {
        $this->rootPath = $rootPath;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        if (!$this->entityManager instanceof EntityManager) {
            $this->entityManager = EntityManager::create(
                $this->getConfiguration()->get('database'),
                Setup::createAnnotationMetadataConfiguration([$this->rootPath . '/src/Classes/Entities/'])
            );
        }
        return $this->entityManager;
    }

    /**
     * @return Server
     */
    public function getServer()
    {
        return new Server(
            function (ServerRequestInterface $request, ResponseInterface $response) {
                $route = $this->getRouteMatcher()->match($request);
                $handlerClassname = $route->handler;
                if (!is_string($handlerClassname) || !class_exists($handlerClassname)) {
                    throw new \Exception('You have to provide proper classnames as handlers in your routes', 1454170067);
                }
                $handler = new $handlerClassname(new View($route->name, $this->getTwigEnvironment()));
                if (!method_exists($handler, $request->getMethod())) {
                    throw new \Exception('Method not supported by handler ' . $handlerClassname, 1454170178);
                }
                $returned = call_user_func([$handler, $request->getMethod()], $request, $response);
                if ($returned) {
                    $response->getBody()->write($returned);
                } elseif (method_exists($handler, 'render')) {
                    $response->getBody()->write($handler->render());
                }
            },
            $this->getRequest(),
            $this->getResponse()
        );
    }

    /**
     * @return Config
     */
    protected function getConfiguration()
    {
        if (!$this->configuration instanceof Config) {
            $paths = [$this->rootPath . '/config/config.json'];
            if (is_readable($this->rootPath . '/config/config.local.json')) {
                $paths[] = $this->rootPath . '/config/config.local.json';
            }
            $this->configuration = Config::load($paths);
        }
        return $this->configuration;
    }

    /**
     * @return ServerRequestInterface
     */
    protected function getRequest()
    {
        if (!$this->request instanceof ServerRequestInterface) {
            $this->request = ServerRequestFactory::fromGlobals();
        }
        return $this->request;
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function getResponse()
    {
        return new Response();
    }

    /**
     * @return Matcher
     * @throws \Exception
     */
    protected function getRouteMatcher()
    {
        if (!$this->routeMatcher instanceof Matcher) {
            $routerContainer = new RouterContainer();
            $routesClassName = $this->getConfiguration()->get('routesClass');
            if (!class_exists($routesClassName)) {
                throw new \Exception('Routes class could not be loaded', 1454173497);
            }
            $routesClass = new $routesClassName;
            if (!$routesClass instanceof RoutesClassInterface) {
                throw new \Exception('Routes class must implement RoutesClassInterface', 1454173584);
            }
            $routesClass->configure($routerContainer->getMap());
            $this->routeMatcher = $routerContainer->getMatcher();
        }
        return $this->routeMatcher;
    }

    /**
     * @return \Twig_Environment
     */
    protected function getTwigEnvironment()
    {
        if (!$this->twigEnvironment instanceof \Twig_Environment) {
            $this->twigEnvironment = new \Twig_Environment(
                new \Twig_Loader_Filesystem($this->rootPath . '/' . $this->getConfiguration()->get('twig.templatesFolder'))
            );
        }
        return $this->twigEnvironment;
    }

}