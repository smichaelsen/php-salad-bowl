<?php
namespace Smichaelsen\SaladBowl\ControllerTraits;

use Doctrine\ORM\EntityManager;
use Smichaelsen\SaladBowl\AuthenticationService;

trait AuthenticationEnabledControllerTrait
{

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var AuthenticationService
     */
    protected $authenticationService;

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
     * @param string $entityName
     * @return object
     */
    protected function getLoggedInUser($entityName)
    {
        $userData = $this->authenticationService->getUserData();
        if ($userData === false) {
            return null;
        }
        return $this->entityManager->getRepository($entityName)->find($userData['id']);
    }
    
}