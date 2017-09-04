<?php

namespace Smichaelsen\SaladBowl;

use Aura\Auth\AuthFactory;
use Aura\Auth\Verifier\PasswordVerifier;
use Aura\Router\Generator;
use Aura\Router\Matcher;
use Aura\Router\RouterContainer;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Noodlehaus\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Smichaelsen\SaladBowl\Plugin\PluginLoader;
use Smichaelsen\SaladBowl\Service\MessageService;
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

    public function __construct(string $rootPath)
    {
        set_exception_handler(function(\Throwable $exception){
            echo 'Oh no. What a mess! An error occured. <pre>';
            var_dump($exception);
            die();
        });
        $this->rootPath = $rootPath;
        $this->serviceContainer = new ServiceContainer();
        $this->initializePlugins();
    }

    public function getEntityManager(): EntityManager
    {
        if (!$this->entityManager instanceof EntityManager) {
            $entityManagerConfiguration = $this->getConfiguration()['entity'];
            $this->entityManager = EntityManager::create(
                $this->getConfiguration()->get('database'),
                Setup::createAnnotationMetadataConfiguration(
                    [
                        $this->rootPath . '/' . ($entityManagerConfiguration['entityDirectory'] ?? 'src/Classes/Entities/'), // application
                        __DIR__ . '/Domain/Entities/', // bowl
                    ],
                    true
                )
            );
        }
        return $this->entityManager;
    }

    public function getServer(): Server
    {
        return new Server(
            function (ServerRequestInterface $request, ResponseInterface $response) {
                $this->checkBasicAuth();
                $dispatch = function (ServerRequestInterface $request, ResponseInterface $response) {
                    $route = $this->getRouteMatcher()->match($request);
                    if ($route === false) {
                        return $response->withStatus(404);
                    }
                    foreach ($route->attributes as $key => $value) {
                        $request = $request->withAttribute($key, $value);
                    }
                    $handlerClassname = $route->handler;
                    if (!is_string($handlerClassname) || !class_exists($handlerClassname)) {
                        throw new \Exception('You have to provide proper classnames as handlers in your routes', 1454170067);
                    }
                    $handler = new $handlerClassname();
                    if (!$handler instanceof ControllerInterface) {
                        throw new \Exception('Handler has to implement the ControllerInterface ', 1454175394);
                    }
                    $appConfiguration = isset($this->getConfiguration()['app']) ? $this->getConfiguration()['app'] : [];
                    $handler->setConfiguration($appConfiguration);
                    $handler->setEntityManager($this->getEntityManager());
                    $view = new View(
                        explode('.', $route->name, 2)[0],
                        $this->getTwigEnvironment()
                    );
                    $view->assign('appConfig', $appConfiguration);
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
                    if (method_exists($handler, 'setMessageService')) {
                        $handler->setMessageService($this->serviceContainer->getSingleton(MessageService::class));
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
                    return $response;
                };
                for ($i = 0; $i < 23; $i++) {
                    try {
                        $response = $dispatch($request, $response);
                        if ($response->getStatusCode() === 404) {
                            $request = $this->getRequest('/404', 'get');
                        } else {
                            break;
                        }
                    } catch (ForwardException $e) {
                        $request = $this->getRequest($e->getPath(), 'get');
                    }
                }
                $this->getEntityManager()->flush();
                return $response;
            },
            $this->getRequest(),
            $this->serviceContainer->getSingleton(Response::class)
        );
    }

    protected function getAuthenticationService(): AuthenticationService
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

    public function getConfiguration(): Config
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

    protected function getPdo(): \PDO
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

    protected function getRequest(string $uriPath = null, string $method = null): ServerRequestInterface
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

    protected function getRouteMatcher(): Matcher
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
            $map = $this->serviceContainer->getSingleton(RouterContainer::class)->getMap();
            $routesClass->configure($map);
            $this->routeMatcher = $this->serviceContainer->getSingleton(RouterContainer::class)->getMatcher();
        }
        return $this->routeMatcher;
    }

    protected function getUrlGenerator(): Generator
    {
        return $this->serviceContainer->getSingleton(RouterContainer::class)->getGenerator();
    }

    protected function getTwigEnvironment(): \Twig_Environment
    {
        if (!$this->twigEnvironment instanceof \Twig_Environment) {
            $twigConfig = $this->getConfiguration()->get('twig');
            if (empty($twigConfig['cache'])) {
                $twigConfig['cache'] = false;
            }
            $templatesFolder = $twigConfig['templatesFolder'] ?? 'templates/';
            $enableDebugMode = $twigConfig['debug'] ?? false;
            $this->twigEnvironment = new \Twig_Environment(
                new \Twig_Loader_Filesystem($this->rootPath . '/' . $templatesFolder),
                [
                    'cache' => $twigConfig['cache'] ?? sys_get_temp_dir(),
                    'debug' => $enableDebugMode,
                ]
            );
            if ($enableDebugMode) {
                $this->twigEnvironment->addExtension(new \Twig_Extension_Debug());
            }
        }
        return $this->twigEnvironment;
    }

    protected function getCsrfTokenManager(): CsrfTokenManager
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

    protected function checkBasicAuth()
    {
        if (PHP_SAPI === 'cli') {
            return;
        }
        $basicAuthConf = $this->getConfiguration()->get('basicAuth');
        if (!is_array($basicAuthConf)) {
            return;
        }
        $isAuthenticated = false;
        if (isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_PW']) {
            foreach ($basicAuthConf['credentials'] as $credential) {
                if ($credential['user'] === $_SERVER['PHP_AUTH_USER'] && $credential['password'] === $_SERVER['PHP_AUTH_PW']) {
                    $isAuthenticated = true;
                }
            }
        }
        if (!$isAuthenticated) {
            header('WWW-Authenticate: Basic realm="' . $basicAuthConf['title'] . '"');
            header('HTTP/1.0 401 Unauthorized');
            exit;
        }
    }

    protected function initializePlugins()
    {
        $this->serviceContainer->getSingleton(PluginLoader::class)($this);
    }

    public function getRootPath(): string
    {
        return $this->rootPath;
    }

    public function getServiceContainer(): ServiceContainer
    {
        return $this->serviceContainer;
    }

}
