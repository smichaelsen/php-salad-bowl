<?php

namespace Smichaelsen\SaladBowl\ControllerTraits;

use Smichaelsen\SaladBowl\Service\MessageService;
use Smichaelsen\SaladBowl\View;

trait MessagingEnabledControllerTrait
{

    /**
     * @var MessageService
     */
    protected $messageService;

    public function setMessageService(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    /**
     * @param View $view
     */
    protected function registerTwigFunctions_messages(View $view)
    {
        static $added = false;
        if ($added) {
            return;
        }
        $added = true;
        $view->addFunction('messages', function ($queue = 'default') {
            $messages = $this->messageService->getMessages($queue);
            if (empty($messages)) {
                return '';
            }
            $content = '<ul class="messages">';
            foreach ($messages as $message) {
                $content .= '<li>' . $message . '</li>';
            }
            $content .= '</ul>';
            return $content;
        });
    }
}