<?php

namespace Smichaelsen\SaladBowl;

use Aura\Router\Generator;
use Aura\Router\Matcher;
use Aura\Router\RouterContainer;
use Doctrine\ORM\EntityManager;
use Noodlehaus\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Smichaelsen\SaladBowl\Domain\Factory\AuthenticationServiceFactory;
use Smichaelsen\SaladBowl\Domain\Factory\EntityManagerFactory;
use Smichaelsen\SaladBowl\Domain\Factory\RouteMatcherFactory;
use Smichaelsen\SaladBowl\Domain\Factory\TwigEnvironmentFactory;
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

    public function getEntityManager(): EntityManager
    {
        if (!$this->entityManager instanceof EntityManager) {
            $this->entityManager = $this->serviceContainer->getSingleton(
                EntityManagerFactory::class,
                $this
            )->create();
        }
        return $this->entityManager;
    }

    public function getServer(): Server
    {
        return new Server(
            function (ServerRequestInterface $request, ResponseInterface $response) {
                $this->checkBasicAuth();
                $dispatch = function (ServerRequestInterface $request, ResponseInterface $response) {
                    $routeMatcher = $this->serviceContainer->getSingleton(
                        RouteMatcherFactory::class,
                        $this
                    )->create();
                    $route = $routeMatcher->match($request);
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
                    $twigEnvironment = $this->getServiceContainer()->getSingleton(
                        TwigEnvironmentFactory::class,
                        $this
                    )->create();
                    $view = new View(explode('.', $route->name, 2)[0], $twigEnvironment);
                    $view->assign('appConfig', $appConfiguration);
                    $handler->setView($view);
                    if (!method_exists($handler, $request->getMethod())) {
                        throw new \Exception('Method ' . $request->getMethod() . ' not supported by handler ' . $handlerClassname, 1454170178);
                    }
                    if (method_exists($handler, 'setAuthenticationService')) {
                        $authenticationService = $this->serviceContainer->getSingleton(
                            AuthenticationServiceFactory::class,
                            $this
                        )->create();
                        $handler->setAuthenticationService($authenticationService);
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

    protected function getUrlGenerator(): Generator
    {
        return $this->serviceContainer->getSingleton(RouterContainer::class)->getGenerator();
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
