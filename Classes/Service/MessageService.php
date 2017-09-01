<?php
namespace Smichaelsen\SaladBowl\Service;

class MessageService
{

    /**
     * @var array
     */
    protected $messages = [];

    /**
     * @param string $message
     * @param string $queue
     */
    public function enqueue($message, $queue = 'default')
    {
        if (empty($this->messages[$queue])) {
            $this->messages[$queue] = [];
        }
        $this->messages[$queue][] = $message;
    }

    /**
     * @param string $queue
     * @return array
     */
    public function getMessages($queue = 'default')
    {
        if (empty($this->messages[$queue])) {
            return [];
        }
        $messages = array_unique($this->messages[$queue]);
        $this->messages[$queue] = [];
        return $messages;
    }

    /**
     * @param string $queue
     * @return bool
     */
    public function hasMessages($queue = 'default')
    {
        return !empty($this->messages[$queue]);
    }
}