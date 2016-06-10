<?php
namespace Smichaelsen\SaladBowl\ControllerInterfaces;

use Smichaelsen\SaladBowl\AuthenticationService;

interface AuthenticationEnabledControllerInterface
{

    /**
     * @param AuthenticationService $authenticationService
     * @return void
     */
    public function setAuthenticationService(AuthenticationService $authenticationService);

}