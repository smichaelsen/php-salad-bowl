<?php
namespace Smichaelsen\SaladBowl;

interface ControllerInterface
{

    /**
     * @param View $view
     * @return void
     */
    public function setView(View $view);

    /**
     * @return string
     */
    public function render();

}
