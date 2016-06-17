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
     * @param $token
     * @return null|Token
     */
    public function getTokenEntity($token)
    {
        $queryBuilder = $this->entityManager->getRepository(Token::class)->createQueryBuilder('t');
        $queryBuilder->select('t')->where(
            $queryBuilder->expr()->orX(
                $queryBuilder->expr()->eq('t.token', ':token'),
                $queryBuilder->expr()->gt('t.expiry', ':expiry')
            )
        )->setParameter('token', $token)->setParameter('expiry', new \DateTime());
        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Token $token
     */
    protected function invalidateToken(Token $token)
    {
        $this->entityManager->remove($token);
    }

}