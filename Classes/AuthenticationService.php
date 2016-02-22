<?php
namespace Smichaelsen\SaladBowl;

use Aura\Auth\Adapter\AdapterInterface;
use Aura\Auth\Auth;
use Aura\Auth\AuthFactory;
use Psr\Http\Message\ServerRequestInterface;

class AuthenticationService
{

    /**
     * @var AdapterInterface
     */
    protected $authenticationAdapter;

    /**
     * @var AuthFactory
     */
    protected $authenticationFactory;

    /**
     * @var bool
     */
    protected $resumed = false;

    /**
     * @var Auth
     */
    protected $authenticationSession;

    /**
     * @param AuthFactory $authenticationFactory
     * @param AdapterInterface $authenticationAdapter
     */
    public function __construct(AuthFactory $authenticationFactory, AdapterInterface $authenticationAdapter)
    {
        $this->authenticationFactory = $authenticationFactory;
        $this->authenticationAdapter = $authenticationAdapter;
    }

    /**
     * @return bool
     */
    public function isAnon()
    {
        $this->resume();
        return $this->authenticationSession->isAnon();
    }

    /**
     * @return bool
     */
    public function isIdle()
    {
        $this->resume();
        return $this->authenticationSession->isIdle();
    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        $this->resume();
        return $this->authenticationSession->isExpired();
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        $this->resume();
        return $this->authenticationSession->isValid();
    }

    /**
     * @param ServerRequestInterface $request
     * @return bool
     */
    public function login(ServerRequestInterface $request)
    {
        $loginService = $this->authenticationFactory->newLoginService($this->authenticationAdapter);
        $loginService->login(
            $this->getAuthenticationSession(),
            $request->getParsedBody()
        );
        $this->resumed = true;
        return $this->isValid();
    }

    /**
     * @return Auth
     */
    protected function getAuthenticationSession()
    {
        if (!$this->authenticationSession instanceof Auth) {
            $this->authenticationSession = $this->authenticationFactory->newInstance();
        }
        return $this->authenticationSession;
    }

    /**
     *
     */
    protected function resume()
    {
        if ($this->resumed === true) {
            return;
        }
        $this->authenticationFactory->newResumeService($this->authenticationAdapter)->resume(
            $this->getAuthenticationSession()
        );
        $this->resumed = true;
    }

}
