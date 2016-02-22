<?php
namespace Smichaelsen\SaladBowl;

use Doctrine\ORM\EntityManager;

abstract class AbstractController implements ControllerInterface
{

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
     * @param $entityName
     * @return object
     */
    protected function getLoggedInUser($entityName)
    {
        if (!isset($this->authenticationService)) {
            return false;
        }
        $userData = $this->authenticationService->getUserData();
        if ($userData === false) {
            return false;
        }
        return $this->entityManager->getRepository($entityName)->find($userData['id']);
    }

    /**
     * @param $path
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
