<?php

namespace MakinaCorpus\Dashboard\Drupal\Page;

use MakinaCorpus\Dashboard\Datasource\DatasourceInterface;
use MakinaCorpus\Dashboard\Datasource\InputDefinition;
use MakinaCorpus\Dashboard\DependencyInjection\AbstractPageDefinition;
use MakinaCorpus\Dashboard\View\Html\TwigView;
use MakinaCorpus\Dashboard\View\ViewDefinition;

/**
 * Default node admin page implementation, suitable for most use cases
 */
class AccountPageDefinition extends AbstractPageDefinition
{
    private $datasource;

    /**
     * Default constructor
     *
     * @param DatasourceInterface $datasource
     */
    public function __construct(DatasourceInterface $datasource)
    {
        $this->datasource = $datasource;
    }

    /**
     * {@inheritdoc}
     */
    public function getInputDefinition(array $options = [])
    {
        return new InputDefinition($this->datasource, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getViewDefinition()
    {
        return new ViewDefinition([
            'properties' => [
                'uid' => true,
                'status' => [
                    'bool_value_false' => t("Blocked"),
                    'bool_value_true' => t("Active"),
                ],
                'name' => true,
                'mail' => [
                    'string_ellipsis' => false,
                    'string_maxlength' => null,
                ],
                'created' => [
                    'callback' => 'format_interval',
                ],
                'access' => [
                    'callback' => 'format_interval',
                ],
                'login' => [
                    'callback' => 'format_interval',
                ],
                'timezone' => [
                    'string_ellipsis' => false,
                    'string_maxlength' => null,
                ],
                'language' => [
                    'string_ellipsis' => false,
                    'string_maxlength' => null,
                ],
            ],
            'templates' => [
                'default' => 'module:udashboard:views/Page/page-dynamic-table.html.twig',
            ],
            'view_type' => TwigView::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getDatasource()
    {
        return $this->datasource;
    }
}
