<?php
namespace Smichaelsen\SaladBowl\Domain\Factory;

use Smichaelsen\SaladBowl\Bowl;

class TwigEnvironmentFactory
{

    /**
     * @var Bowl
     */
    protected $bowl;

    public function __construct(Bowl $bowl)
    {
        $this->bowl = $bowl;
    }

    public function create(): \Twig_Environment
    {
        $twigConfig = $this->bowl->getConfiguration()->get('twig');
        if (empty($twigConfig['cache'])) {
            $twigConfig['cache'] = false;
        }
        $templatesFolder = $twigConfig['templatesFolder'] ?? 'templates/';
        $enableDebugMode = $twigConfig['debug'] ?? false;
        $twigEnvironment = new \Twig_Environment(
            new \Twig_Loader_Filesystem($this->bowl->getRootPath() . '/' . $templatesFolder),
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