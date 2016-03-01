<?php
namespace Smichaelsen\SaladBowl\ControllerInterfaces;

use Smichaelsen\SaladBowl\MailService;

interface MailEnabledControllerInterface
{

    /**
     * @param MailService $mailService
     * @return void
     */
    public function setMailService(MailService $mailService);

}
