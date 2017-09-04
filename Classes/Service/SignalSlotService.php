<?php

namespace Smichaelsen\SaladBowl\Service;

class SignalSlotService
{

    protected $registeredSlots = [];

    /**
     * @param string $signalName
     * @param $callback
     * @throws \Exception
     */
    public function register($signalName, $callback)
    {
        if (!isset($this->registeredSlots[$signalName])) {
            $this->registeredSlots[$signalName] = [];
        }
        $this->registeredSlots[$signalName][] = $callback;
    }

    /**
     * @param string $signalName
     * @param array ...$arguments
     */
    public function dispatchSignal($signalName, ...$arguments)
    {
        if (isset($this->registeredSlots[$signalName])) {
            foreach ($this->registeredSlots[$signalName] as $callback) {
                $callback(...$arguments);
            }
        }
    }
}