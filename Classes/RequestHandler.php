<?php

namespace Smichaelsen\SaladBowl;

use Aura\Router\Generator;
use Aura\Router\RouterContainer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Smichaelsen\SaladBowl\Domain\Factory\AuthenticationServiceFactory;
use Smichaelsen\SaladBowl\Domain\Factory\RequestFactory;
use Smichaelsen\SaladBowl\Domain\Factory\RouteMatcherFactory;
use Smichaelsen\SaladBowl\Domain\Factory\TwigEnvironmentFactory;
use Smichaelsen\SaladBowl\Service\MailService;
use Smichaelsen\SaladBowl\Service\MessageService;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;

class RequestHandler
{

    /**
     * @var Bowl
     */
    protected $bowl;

    public function __construct(Bowl $bowl)
    {
        $this->bowl = $bowl;
    }

    public function handle(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->checkBasicAuth();
        $dispatch = function (ServerRequestInterface $request, ResponseInterface $response) {
            $routeMatcher = ServiceContainer::getSingleton(RouteMatcherFactory::class)->create();
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
            $appConfiguration = Bowl::getConfiguration('app', []);
            $handler->setConfiguration($appConfiguration);
            $handler->setEntityManager($this->bowl->getEntityManager());
            $twigEnvironment = ServiceContainer::getSingleton(TwigEnvironmentFactory::class)->create();
            $view = new View(explode('.', $route->name, 2)[0], $twigEnvironment);
            $view->assign('appConfig', $appConfiguration);
            $handler->setView($view);
            if (!method_exists($handler, $request->getMethod())) {
                throw new \Exception('Method ' . $request->getMethod() . ' not supported by handler ' . $handlerClassname, 1454170178);
            }
            if (method_exists($handler, 'setAuthenticationService')) {
                $authenticationService = ServiceContainer::getSingleton(AuthenticationServiceFactory::class)->create();
                $handler->setAuthenticationService($authenticationService);
            }
            if (method_exists($handler, 'setCsrfTokenManager')) {
                $handler->setCsrfTokenManager($this->getCsrfTokenManager());
            }
            if (method_exists($handler, 'setMailService')) {
                $handler->setMailService(ServiceContainer::getSingleton(MailService::class, Bowl::getConfiguration('swiftmailer')));
            }
            if (method_exists($handler, 'setMessageService')) {
                $handler->setMessageService(ServiceContainer::getSingleton(MessageService::class));
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
                    $request = RequestFactory::create('/404', 'get');
                } else {
                    break;
                }
            } catch (ForwardException $e) {
                $request = RequestFactory::create($e->getPath(), 'get');
            }
        }
        $this->bowl->getEntityManager()->flush();
        return $response;
    }

    protected function checkBasicAuth()
    {
        if (PHP_SAPI === 'cli') {
            return;
        }
        $basicAuthConf = Bowl::getConfiguration('basicAuth');
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

    protected function getCsrfTokenManager(): CsrfTokenManager
    {
        $session = ServiceContainer::getSingleton(Session::class);
        $session->start();
        return ServiceContainer::getSingleton(
            CsrfTokenManager::class,
            ServiceContainer::getSingleton(UriSafeTokenGenerator::class),
            ServiceContainer::getSingleton(SessionTokenStorage::class, $session)
        );
    }

    protected function getUrlGenerator(): Generator
    {
        return ServiceContainer::getSingleton(RouterContainer::class)->getGenerator();
    }
}