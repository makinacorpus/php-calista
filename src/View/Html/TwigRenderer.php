<?php

namespace MakinaCorpus\Calista\View\Html;

/**
 * Context variable for twig templates and main renderer for pages
 */
class TwigRenderer
{
    private $twig;
    private $template;
    private $arguments;

    /**
     * Default constructor
     *
     * @param \Twig_Environment $twig
     * @param string $template
     * @param mixed[] $arguments
     */
    public function __construct(\Twig_Environment $twig, $template, array $arguments = [])
    {
        $this->twig = $twig;
        $this->template = $template;
        $this->arguments = $arguments;
    }

    /**
     * Render a single block of this page
     *
     * @param string $block
     *
     * @return string
     */
    public function renderPartial($block)
    {
        return $this->twig->load($this->template)->renderBlock($block, $this->arguments);
    }

    /**
     * Get arguments
     *
     * @return mixed[]
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Render the page
     *
     * @return string
     */
    public function render()
    {
        return $this->renderPartial('page');
    }
}
