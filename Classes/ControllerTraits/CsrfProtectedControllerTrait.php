<?php
namespace Smichaelsen\SaladBowl\ControllerTraits;

use Smichaelsen\SaladBowl\View;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManager;

trait CsrfProtectedControllerTrait
{

    /**
     * @var CsrfTokenManager
     */
    protected $csrfTokenManager;

    public function setCsrfTokenManager(CsrfTokenManager $csrfTokenManager)
    {
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * @param string $tokenId
     * @param string $value
     * @throws \Exception
     */
    protected function validateCsrfToken($tokenId, $value)
    {
        if ($this->csrfTokenManager->isTokenValid(new CsrfToken($tokenId, $value))) {
            return;
        }
        throw new \Exception('Invalid csrf token. Please try again', 1465918041);
    }

    /**
     * @param View $view
     */
    protected function registerTwigFunctions_csrf(View $view) {
        if (isset($this->csrfTokenManager)) {
            /** @var CsrfTokenManager $csrfTokenManager */
            $csrfTokenManager = $this->csrfTokenManager;
            $view->addFunction('csrfToken', function ($tokenId) use ($csrfTokenManager) {
                return $csrfTokenManager->getToken($tokenId);
            });
        }
    }

}