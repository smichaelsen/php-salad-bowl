<?php
namespace Smichaelsen\SaladBowl;

use Doctrine\ORM\EntityManager;

interface ControllerInterface
{

    /**
     * @param EntityManager $entityManager
     * @return void
     */
    public function setEntityManager(EntityManager $entityManager);

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

    /**
     * Your controller *can* implement the following method. If so, it will will be called after construction
     * right before the request method is called.
     *
     * @return void
     */
    // public function initializeAction();

}
