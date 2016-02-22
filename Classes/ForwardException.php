<?php
namespace Smichaelsen\SaladBowl;

class ForwardException extends \Exception
{

    /**
     * @var string
     */
    protected $path;

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

}
