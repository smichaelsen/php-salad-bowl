<?php
namespace Smichaelsen\SaladBowl\ControllerTraits;

use Doctrine\ORM\EntityManager;
use Smichaelsen\SaladBowl\Domain\Entities\Token;
use Smichaelsen\SaladBowl\Domain\Factory\TokenFactory;

trait TokenEnabledControllerTrait
{

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param string|null $expiresIn
     * @return \Smichaelsen\SaladBowl\Domain\Entities\Token
     */
    public function generateToken($expiresIn)
    {
        static $tokenFactory;
        if (!$tokenFactory instanceof TokenFactory) {
            $tokenFactory = new TokenFactory();
        }
        $token = $tokenFactory->create($expiresIn);
        $this->entityManager->persist($token);
        return $token;
    }

    /**
     * @param Token $token
     */
    protected function invalidateToken(Token $token)
    {
        $this->entityManager->detach($token);
    }

}