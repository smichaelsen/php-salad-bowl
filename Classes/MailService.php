<?php
namespace Smichaelsen\SaladBowl;

class MailService
{

    /**
     * @var array
     */
    protected $configuration;

    /**
     * @param array $configuration
     */
    public function __construct($configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @return \Swift_Mailer
     * @throws \Exception
     */
    public function getSwiftMailer()
    {
        if ($this->configuration['transport'] !== 'smtp') {
            throw new \Exception('The MailService only supports SMTP at the moment', 1456822674);
        }
        $transport = new \Swift_SmtpTransport($this->configuration['smtp_host'], $this->configuration['smtp_port']);
        if (!empty($this->configuration['smtp_username'])) {
            $transport->setUsername($this->configuration['smtp_username']);
        }
        if (!empty($this->configuration['smtp_password'])) {
            $transport->setPassword($this->configuration['smtp_password']);
        }
        return \Swift_Mailer::newInstance($transport);
    }

}
