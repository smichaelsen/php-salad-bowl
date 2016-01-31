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

    /**
     * Your controller *can* implement the following method. If so, it will receive a ready to use
     * AuthenticationService after construction.
     *
     * @param \Smichaelsen\SaladBowl\AuthenticationService $authenticationService
     * @return void
     */
    // public function setAuthenticationService(\Smichaelsen\SaladBowl\AuthenticationService $authenticationService);

}
