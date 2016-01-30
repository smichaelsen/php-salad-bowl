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
     * @var string
     */
    protected $rootPath;

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
        if (!isset($GLOBALS['entityManager']) || !$GLOBALS['entityManager'] instanceof EntityManager) {
            $GLOBALS['entityManager'] = EntityManager::create(
                $this->getConfiguration()->get('database'),
                Setup::createAnnotationMetadataConfiguration([$this->rootPath . '/src/Classes/Entities/'])
            );
        }
        return $GLOBALS['entityManager'];
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
        if (!isset($GLOBALS['configuration']) || !$GLOBALS['configuration'] instanceof Config) {
            $GLOBALS['configuration'] = Config::load($this->rootPath . '/config/config.json');
        }
        return $GLOBALS['configuration'];
    }

    /**
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    protected function getRequest()
    {
        return ServerRequestFactory::fromGlobals();
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
        if (!isset($GLOBALS['routeMatcher']) || !$GLOBALS['routeMatcher'] instanceof Matcher) {
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
            $GLOBALS['routeMatcher'] = $routerContainer->getMatcher();
        }
        return $GLOBALS['routeMatcher'];
    }

    /**
     * @return \Twig_Environment
     */
    protected function getTwigEnvironment()
    {
        if (!isset($GLOBALS['twigEnvironment']) || !$GLOBALS['twigEnvironment'] instanceof \Twig_Environment) {
            $GLOBALS['twigEnvironment'] = new \Twig_Environment(
                new \Twig_Loader_Filesystem($this->rootPath . '/' . $this->getConfiguration()->get('twig.templatesFolder'))
            );
        }
        return $GLOBALS['twigEnvironment'];
    }

}