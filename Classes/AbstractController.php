<?php
namespace Smichaelsen\SaladBowl;

use Doctrine\ORM\EntityManager;
use Smichaelsen\SaladBowl\ControllerInterfaces\ControllerInterface;

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

    /**
     * 
     */
    protected function registerCoreTwigFunctions()
    {
        $methods = get_class_methods($this);
        foreach ($methods as $method) {
            if (strpos($method, 'registerTwigFunctions_') === 0) {
                $this->{$method}($this->view);
            }
        }
    }

}
