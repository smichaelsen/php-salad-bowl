<?php
namespace Smichaelsen\SaladBowl;

use Aura\Router\Map;

interface RoutesClassInterface
{

    /**
     * @param Map $map
     * @return void
     */
    public function configure(Map $map);

}