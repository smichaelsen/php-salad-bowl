<?php
namespace Smichaelsen\SaladBowl\ControllerTraits;

use Doctrine\ORM\EntityManager;
use Smichaelsen\SaladBowl\AuthenticationService;

/**
 * Controllers that use this trait will get a $this->authenticationService injected automatically.
 * See \Smichaelsen\SaladBowl\AuthenticationService and Aura.Auth for its features.
 */
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
     * @param AuthenticationService $authenticationService
     * @return void
     */
    public function setAuthenticationService(AuthenticationService $authenticationService)
    {
        $this->authenticationService = $authenticationService;
    }

    protected function getLoggedInUser(string $entityName): object
    {
        $userData = $this->authenticationService->getUserData();
        if ($userData === false) {
            return null;
        }
        return $this->entityManager->getRepository($entityName)->find($userData['id']);
    }

}
