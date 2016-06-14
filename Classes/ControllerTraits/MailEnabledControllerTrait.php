<?php
namespace Smichaelsen\SaladBowl\ControllerTraits;

use Smichaelsen\SaladBowl\MailService;

trait MailEnabledControllerTrait
{

    /** @var MailService */
    protected $mailService;

    /**
     * @param MailService $mailService
     * @return void
     */
    public function setMailService(MailService $mailService)
    {
        $this->mailService = $mailService;
    }

}
