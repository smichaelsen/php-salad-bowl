<?php

namespace Smichaelsen\SaladBowl\ConfigReader;

use Helhum\ConfigLoader\Reader\ConfigReaderInterface;

class JsonFileReader implements ConfigReaderInterface
{

    /**
     * @var string
     */
    private $configFile;

    public function __construct(string $configFile)
    {
        $this->configFile = $configFile;
    }

    public function hasConfig(): bool
    {
        return file_exists($this->configFile);
    }

    public function readConfig(): array
    {
        return json_decode(file_get_contents($this->configFile), true);
    }
}