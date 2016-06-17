<?php
namespace Smichaelsen\SaladBowl\Domain\Factory;

use Smichaelsen\SaladBowl\Domain\Entities\Token;

class TokenFactory
{

    /**
     * @param string|null $expiresIn
     * @return Token
     */
    public function create($expiresIn)
    {
        $tokenEntity = new Token($expiresIn);
        $token = bin2hex(openssl_random_pseudo_bytes(16));
        $tokenEntity->setToken($token);
        if ($expiresIn !== null) {
            $tokenEntity->setExpiry(new \DateTime($expiresIn));
        }
        return $tokenEntity;
    }

}