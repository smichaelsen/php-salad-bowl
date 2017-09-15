<?php

namespace Smichaelsen\SaladBowl\Plugin;

use Smichaelsen\SaladBowl\Bowl;

class PluginLoader
{

    public function __invoke(Bowl $bowl)
    {
        $composerJsonPath = BOWL_ROOT_PATH . '/vendor/composer/installed.json';
        $installedPackages = json_decode(file_get_contents($composerJsonPath), true);
        foreach ($installedPackages as $package) {
            if ($package['type'] === 'salad-bowl-plugin') {
                if (isset($package['extra']['salad-bowl-plugin-class'])) {
                    $pluginClass = $package['extra']['salad-bowl-plugin-class'];
                    if (class_exists($pluginClass)) {
                        /** @var PluginInterface $plugin */
                        $plugin = new $pluginClass;
                        if (!$plugin instanceof PluginInterface) {
                            throw new \Exception(
                                sprintf(
                                    '%s doesn\'t implement the PluginInterface',
                                    get_class($plugin)
                                ),
                                1504555641
                            );
                        }
                        $plugin->register($bowl);
                    }
                }
            }
        }
    }

}