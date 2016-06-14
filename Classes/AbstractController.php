<?php
namespace Smichaelsen\SaladBowl;

use Aura\Router\Generator;
use Doctrine\ORM\EntityManager;
use Smichaelsen\SaladBowl\ControllerInterfaces\ControllerInterface;
use Smichaelsen\SaladBowl\ControllerInterfaces\UrlGeneratorEnabledControllerInterface;

abstract class AbstractController implements ControllerInterface, UrlGeneratorEnabledControllerInterface
{

    /**
     * @var AuthenticationService
     */
    protected $authenticationService;

    /**
     * @var array
     */
    protected $configuration;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var Generator
     */
    protected $urlGenerator;

    /**
     * @var View
     */
    protected $view;

    /**
     * AbstractController constructor.
     */
    public function __construct()
    {
    }

    /**
     * Will only be called if the controller implements the AuthenticationEnabledControllerInterface
     *
     * @param AuthenticationService $authenticationService
     * @return void
     */
    public function setAuthenticationService(AuthenticationService $authenticationService)
    {
        $this->authenticationService = $authenticationService;
    }

    /**
     * @param array $configuration
     */
    public function setConfiguration(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param EntityManager $entityManager
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param Generator $generator
     */
    public function setUrlGenerator(Generator $generator)
    {
        $this->urlGenerator = $generator;
    }

    /**
     * @param View $view
     */
    public function setView(View $view)
    {
        $this->view = $view;
    }

    /**
     * @param string $entityName
     * @return object
     */
    protected function getLoggedInUser($entityName)
    {
        if (!isset($this->authenticationService)) {
            return null;
        }
        $userData = $this->authenticationService->getUserData();
        if ($userData === false) {
            return null;
        }
        return $this->entityManager->getRepository($entityName)->find($userData['id']);
    }

    /**
     * @param string $path
     * @throws ForwardException
     */
    public function forward($path)
    {
        $forwardException = new ForwardException();
        $forwardException->setPath($path);
        throw $forwardException;
    }

    /**
     *
     */
    public function initializeAction()
    {
        $this->registerCoreTwigFunctions();
    }

    /**
     * @param string $path
     */
    public function redirect($path)
    {
        header('Location: ' . '//' . $_SERVER['HTTP_HOST'] . '/' . trim($path, '/'));
        die();
    }

    /**
     * @return string
     */
    public function render()
    {
        return $this->view->render();
    }

    protected function registerCoreTwigFunctions()
    {
        $urlGenerator = $this->urlGenerator;
        $this->view->addFunction('path', function ($routeName, $arguments = []) use ($urlGenerator) {
            return $urlGenerator->generate($routeName, $arguments);
        });
    }

}
