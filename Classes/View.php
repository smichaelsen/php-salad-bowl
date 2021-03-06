<?php
namespace Smichaelsen\SaladBowl;

class View
{

    /**
     * @var string
     */
    protected $templateName;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var array
     */
    protected $variables = [];

    /**
     * View constructor.
     * @param string $templateName
     * @param \Twig_Environment $twig
     */
    public function __construct($templateName, \Twig_Environment $twig)
    {
        $this->templateName = $templateName;
        $this->twig = $twig;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function assign($key, $value)
    {
        $this->variables[$key] = $value;
    }

    /**
     * @param string $key
     */
    public function unassign($key)
    {
        unset($this->variables[$key]);
    }

    /**
     * @return string
     */
    public function render()
    {
        return $this->twig->render($this->templateName . '.twig', $this->variables);
    }

    /**
     * @param string $name
     * @param \Closure $filter
     */
    public function addFilter($name, \Closure $filter)
    {
        $this->twig->addFilter(new \Twig_SimpleFilter($name, $filter));
    }

    /**
     * @param string $name
     * @param \Closure $function
     */
    public function addFunction($name, \Closure $function)
    {
        $this->twig->addFunction(new \Twig_SimpleFunction($name, $function));
    }

}
