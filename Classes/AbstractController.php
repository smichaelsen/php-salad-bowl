<?php
namespace Smichaelsen\SaladBowl;

class AbstractController
{

    /**
     * @var \Twig_Environment
     */
    protected $view;

    /**
     * @param View $view
     */
    public function __construct(View $view)
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