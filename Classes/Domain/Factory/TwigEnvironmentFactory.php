<?php
namespace Smichaelsen\SaladBowl\Domain\Factory;

use Smichaelsen\SaladBowl\Bowl;

class TwigEnvironmentFactory
{

    public function create(): \Twig_Environment
    {
        $twigConfig = Bowl::getConfiguration('twig');
        if (empty($twigConfig['cache'])) {
            $twigConfig['cache'] = false;
        }
        $templatesFolder = $twigConfig['templatesFolder'] ?? 'templates/';
        $enableDebugMode = $twigConfig['debug'] ?? false;
        $twigEnvironment = new \Twig_Environment(
            new \Twig_Loader_Filesystem(BOWL_ROOT_PATH . '/' . $templatesFolder),
            [
                'cache' => $twigConfig['cache'] ?? sys_get_temp_dir(),
                'debug' => $enableDebugMode,
            ]
        );
        if ($enableDebugMode) {
            $twigEnvironment->addExtension(new \Twig_Extension_Debug());
        }
        return $twigEnvironment;
    }
}