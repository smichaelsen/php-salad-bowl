<?php

namespace Smichaelsen\SaladBowl\Domain\Factory;

use Helhum\ConfigLoader\ConfigurationLoader;
use Helhum\ConfigLoader\Reader\EnvironmentReader;
use Smichaelsen\SaladBowl\ConfigReader\JsonFileReader;

class ConfigurationFactory
{

    public function create()
    {
        $configLoader = new ConfigurationLoader(
            [
                new JsonFileReader(BOWL_ROOT_PATH . '/config/config.json'),
                new JsonFileReader(BOWL_ROOT_PATH . '/config/config.local.json'),
                new EnvironmentReader('BOWL'),
            ]
        );
        return $configLoader->load();
    }

}