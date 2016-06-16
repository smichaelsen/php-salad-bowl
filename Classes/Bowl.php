<?php
namespace Smichaelsen\SaladBowl;

use Aura\Auth\AuthFactory;
use Aura\Auth\Verifier\PasswordVerifier;
use Aura\Router\Matcher;
use Aura\Router\RouterContainer;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Noodlehaus\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;
use Zend\Diactoros\Response;
use Zend\Diactoros\Server;
use Zend\Diactoros\ServerRequestFactory;

class Bowl
{

    /**
     * @var AuthenticationService
     */
    protected $authenticationService;

    /**
     * @var Config
     */
    protected $configuration;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var \PDO
     */
    protected $pdo;

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
     * @var ServiceContainer
     */
    protected $serviceContainer;

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
        $this->serviceContainer = new ServiceContainer($this->getConfiguration());
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        if (!$this->entityManager instanceof EntityManager) {
            $this->entityManager = EntityManager::create(
                $this->getConfiguration()->get('database'),
                Setup::createAnnotationMetadataConfiguration(
                    [
                        $this->rootPath . '/src/Classes/Entities/', // application
                        __DIR__ . '/Domain/Entities/', // bowl
                    ],
                    true
                )
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
                $dispatch = function (ServerRequestInterface $request, ResponseInterface $response) {
                    $route = $this->getRouteMatcher()->match($request);
                    foreach ($route->attributes as $key => $value) {
                        $request = $request->withAttribute($key, $value);
                    }
                    if ($route === false) {
                        $forwardException = new ForwardException();
                        $forwardException->setPath('/404');
                        throw $forwardException;
                    }
                    $handlerClassname = $route->handler;
                    if (!is_string($handlerClassname) || !class_exists($handlerClassname)) {
                        throw new \Exception('You have to provide proper classnames as handlers in your routes', 1454170067);
                    }
                    $handler = new $handlerClassname();
                    if (!$handler instanceof ControllerInterface) {
                        throw new \Exception('Handler has to implement the ControllerInterface ', 1454175394);
                    }
                    $handler->setConfiguration(isset($this->getConfiguration()['app']) ? $this->getConfiguration()['app'] : []);
                    $handler->setEntityManager($this->getEntityManager());
                    $view = new View(
                        explode('.', $route->name, 2)[0],
                        $this->getTwigEnvironment()
                    );
                    $handler->setView($view);
                    if (!method_exists($handler, $request->getMethod())) {
                        throw new \Exception('Method ' . $request->getMethod() . ' not supported by handler ' . $handlerClassname, 1454170178);
                    }
                    if (method_exists($handler, 'setAuthenticationService')) {
                        $handler->setAuthenticationService($this->getAuthenticationService());
                    }
                    if (method_exists($handler, 'setCsrfTokenManager')) {
                        $handler->setCsrfTokenManager($this->getCsrfTokenManager());
                    }
                    if (method_exists($handler, 'setMailService')) {
                        $handler->setMailService($this->serviceContainer->getSingleton(MailService::class, $this->getConfiguration()->get('swiftmailer')));
                    }
                    if (method_exists($handler, 'setUrlGenerator')) {
                        $handler->setUrlGenerator($this->getUrlGenerator());
                    }
                    if (method_exists($handler, 'initializeAction')) {
                        $handler->initializeAction();
                    }
                    $returned = call_user_func([$handler, $request->getMethod()], $request, $response);
                    if ($returned) {
                        $response->getBody()->write($returned);
                    } else {
                        $response->getBody()->write($handler->render());
                    }
                };
                for ($i = 0; $i < 23; $i++) {
                    try {
                        $dispatch($request, $response);
                        break;
                    } catch (ForwardException $e) {
                        $request = $this->getRequest($e->getPath(), 'get');
                    }
                }
                $this->getEntityManager()->flush();
            },
            $this->getRequest(),
            $this->serviceContainer->getSingleton(Response::class)
        );
    }

    /**
     * @return AuthenticationService
     * @throws \Exception
     */
    protected function getAuthenticationService()
    {
        if (!$this->authenticationService instanceof AuthenticationService) {
            $authConfig = $this->getConfiguration()->get('authentication');
            if (!is_array($authConfig)) {
                throw new \Exception('Authentication configuration missing', 1465583676);
            }
            $authFactory = new AuthFactory($_COOKIE);
            $authAdapter = $authFactory->newPdoAdapter(
                $this->getPdo(),
                new PasswordVerifier(AuthenticationService::PASSWORD_HASHING_ALGO),
                $authConfig['columns'],
                $authConfig['table']
            );
            $this->authenticationService = new AuthenticationService($authFactory, $authAdapter);
        }
        return $this->authenticationService;
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
     * @return \PDO
     */
    protected function getPdo()
    {
        if (!$this->pdo instanceof \PDO) {
            $dbConfig = $this->getConfiguration()->get('database');
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s',
                $dbConfig['host'],
                $dbConfig['dbname']
            );
            $this->pdo = new \PDO($dsn, $dbConfig['user'], $dbConfig['password']);
        }
        return $this->pdo;
    }

    /**
     * @param string $uriPath
     * @param string $method
     * @return ServerRequestInterface
     */
    protected function getRequest($uriPath = null, $method = null)
    {
        if ($uriPath !== null) {
            $server = $_SERVER;
            $server['REQUEST_URI'] = $uriPath;
            if ($method !== null) {
                $server['REQUEST_METHOD'] = strtoupper($method);
            }
            return ServerRequestFactory::fromGlobals($server);
        }
        if (!$this->request instanceof ServerRequestInterface) {
            $this->request = ServerRequestFactory::fromGlobals();
        }
        return $this->request;
    }

    /**
     * @return Matcher
     * @throws \Exception
     */
    protected function getRouteMatcher()
    {
        if (!$this->routeMatcher instanceof Matcher) {
            $routesClassName = $this->getConfiguration()->get('routesClass');
            if (!class_exists($routesClassName)) {
                throw new \Exception('Routes class could not be loaded', 1454173497);
            }
            $routesClass = new $routesClassName;
            if (!$routesClass instanceof RoutesClassInterface) {
                throw new \Exception('Routes class must implement RoutesClassInterface', 1454173584);
            }
            $routesClass->configure($this->serviceContainer->getSingleton(RouterContainer::class)->getMap());
            $this->routeMatcher = $this->serviceContainer->getSingleton(RouterContainer::class)->getMatcher();
        }
        return $this->routeMatcher;
    }

    /**
     * @return \Aura\Router\Generator
     */
    protected function getUrlGenerator()
    {
        return $this->serviceContainer->getSingleton(RouterContainer::class)->getGenerator();
    }

    /**
     * @return \Twig_Environment
     */
    protected function getTwigEnvironment()
    {
        if (!$this->twigEnvironment instanceof \Twig_Environment) {
            $twigConfig = $this->getConfiguration()->get('twig');
            if (isset($twigConfig['cache']) && $twigConfig['cache'] === '') {
                $twigConfig['cache'] = false;
            }
            $this->twigEnvironment = new \Twig_Environment(
                new \Twig_Loader_Filesystem($this->rootPath . '/' . $twigConfig['templatesFolder']),
                ['cache' => isset($twigConfig['cache']) ? $twigConfig['cache'] : sys_get_temp_dir()]
            );
        }
        return $this->twigEnvironment;
    }

    /**
     * @return CsrfTokenManager
     */
    protected function getCsrfTokenManager()
    {
        $session = $this->serviceContainer->getSingleton(Session::class);
        $session->start();
        return $this->serviceContainer->getSingleton(
            CsrfTokenManager::class,
            $this->serviceContainer->getSingleton(UriSafeTokenGenerator::class),
            $this->serviceContainer->getSingleton(
                SessionTokenStorage::class,
                $session
            )
        );
    }

}
