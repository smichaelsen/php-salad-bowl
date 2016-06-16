<?php
namespace Smichaelsen\SaladBowl\Domain\Entities;

/**
 * @Entity @Table(name="token")
 */
class Token
{

    /**
     * @Id @Column(type="integer") @GeneratedValue
     * @var int
     */
    protected $id;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $token;

    /**
     * @Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    protected $expiry;

    /**
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param \DateTime $expiry
     */
    public function setExpiry(\DateTime $expiry)
    {
        $this->expiry = $expiry;
    }

    /**
     * @param $tokenToCompare
     * @return bool
     */
    public function validate($tokenToCompare)
    {
        return ($this->token === $tokenToCompare && $this->expiry < (new \DateTime()));
    }

}