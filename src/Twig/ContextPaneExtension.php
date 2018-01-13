<?php

namespace MakinaCorpus\Calista\Twig;

use MakinaCorpus\Calista\Context\ContextPane;

class ContextPaneExtension extends \Twig_Extension
{
    private $contextPane;

    /**
     * Default constructor
     */
    public function __construct(ContextPane $contextPane)
    {
        $this->contextPane = $contextPane;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('context_pane', [$this, 'renderContextPane'], ['is_safe' => ['html'], 'needs_environment' => true]),
        ];
    }

    /**
     * Render context pane
     */
    public function renderContextPane(\Twig_Environment $environment)
    {
        $this->contextPane->init();

        return $environment->render('@calista/context/pane.html.twig', ['context' => $this->contextPane]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'calista_context_pane';
    }
}
