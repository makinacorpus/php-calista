<?php

namespace MakinaCorpus\Calista\Twig;

/**
 * Provides a few donwgrade compability functions.
 *
 * @codeCoverageIgnore
 */
class DowngradeCompatibilityExtension extends \Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('form_widget', [$this, 'renderFormWidget'], ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('form_errors', [$this, 'renderFormErrors'], ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('form_rest', [$this, 'renderFormRest'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Sorry, this won't render anything. At all.
     */
    public function renderFormWidget()
    {
        return '';
    }

    /**
     * Sorry, this won't render anything. At all.
     */
    public function renderFormErrors()
    {
        return '';
    }

    /**
     * Sorry, this won't render anything. At all.
     */
    public function renderFormRest()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'calista_downgrade_compatibility';
    }
}
