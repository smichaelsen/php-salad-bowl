<?php
namespace Smichaelsen\SaladBowl;

abstract class AbstractController implements ControllerInterface
{

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
     * @param View $view
     */
    public function setView(View $view)
    {
        $this->view = $view;
    }

    /**
     * @return string
     */
    public function render()
    {
        return $this->view->render();
    }

}
