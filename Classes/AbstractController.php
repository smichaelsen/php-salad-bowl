<?php
namespace Smichaelsen\SaladBowl;

use Doctrine\ORM\EntityManager;

abstract class AbstractController implements ControllerInterface
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

}
