<?php
namespace Smichaelsen\SaladBowl;

use Doctrine\ORM\EntityManager;

abstract class AbstractController implements ControllerInterface
{

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
