<?php
namespace Smichaelsen\SaladBowl\Domain\Factory;

use Aura\Auth\AuthFactory;
use Aura\Auth\Verifier\PasswordVerifier;
use Smichaelsen\SaladBowl\AuthenticationService;
use Smichaelsen\SaladBowl\Bowl;

class AuthenticationServiceFactory
{

    /**
     * @var Bowl
     */
    protected $bowl;

    public function __construct(Bowl $bowl)
    {
        $this->bowl = $bowl;
    }

    public function create(): AuthenticationService
    {
        $authConfig = $this->bowl->getConfiguration()->get('authentication');
        if (!is_array($authConfig)) {
            throw new \Exception('Authentication configuration missing', 1465583676);
        }
        $authFactory = new AuthFactory($_COOKIE);
        $authAdapter = $authFactory->newPdoAdapter(
            $this->getPdo(),
            new PasswordVerifier(AuthenticationService::PASSWORD_HASHING_ALGO),
            $authConfig['columns'],
            $authConfig['table']
        );
        return new AuthenticationService($authFactory, $authAdapter);
    }

    protected function getPdo(): \PDO
    {
        $dbConfig = $this->bowl->getConfiguration()->get('database');
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s',
            $dbConfig['host'],
            $dbConfig['dbname']
        );
        return new \PDO($dsn, $dbConfig['user'], $dbConfig['password']);
    }

}