<?php
namespace Smichaelsen\SaladBowl;

use Aura\Auth\Adapter\AdapterInterface;
use Aura\Auth\Auth;
use Aura\Auth\AuthFactory;
use Psr\Http\Message\ServerRequestInterface;

class AuthenticationService
{

    const PASSWORD_HASHING_ALGO = PASSWORD_DEFAULT;

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
     * @param array|ServerRequestInterface $data
     * @return bool
     */
    public function login($data)
    {
        if ($data instanceof ServerRequestInterface) {
            $data = $data->getParsedBody();
        }
        $loginService = $this->authenticationFactory->newLoginService($this->authenticationAdapter);
        $loginService->login(
            $this->getAuthenticationSession(),
            [
                'username' => $data['username'],
                'password' => $data['password'],
            ]
        );
        $this->resumed = true;
        return $this->isValid();
    }

    /**
     *
     */
    public function logout()
    {
        $this->resume();
        $logoutService = $this->authenticationFactory->newLogoutService($this->authenticationAdapter);
        $logoutService->logout($this->getAuthenticationSession());
    }

    /**
     * @return array|bool
     */
    public function getUserData()
    {
        if (!$this->isValid()) {
            return false;
        }
        return $this->authenticationSession->getUserData();
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

    /**
     * @param string $password
     * @return string
     */
    public function hashPassword($password)
    {
        return password_hash($password, self::PASSWORD_HASHING_ALGO);
    }

}
