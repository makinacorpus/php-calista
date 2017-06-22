<?php

namespace MakinaCorpus\Dashboard\Drupal\Page;

use MakinaCorpus\Dashboard\Datasource\DatasourceInterface;
use MakinaCorpus\Dashboard\Datasource\InputDefinition;
use MakinaCorpus\Dashboard\DependencyInjection\DynamicPageDefinition;
use MakinaCorpus\Dashboard\View\Html\TwigView;

/**
 * Default node admin page implementation, suitable for most use cases
 *
 * @todo
 *   - datasource handling in datasource in parent class
 *   - make input definition optional
 *   - handle virtual columns (non existing in property info)
 *      -> allow non existing columns in property info to be displayed in dynamic template
 *      -> even if value is null, execute callback on it in twig extension
 *   - pass the complete item in the callback method signature
 *   - check for method parameters names (if value => value, if item => item)
 *      -> do a proxy callback in the parent implementation that passes the right
 *         value that the user await here
 */
class AccountPageDefinition extends DynamicPageDefinition
{
    private $datasource;

    public $uid = 0;
    public $name = '';
    public $mail = '';
    public $created = 0;
    public $changed = 0;
    public $access = 0;
    public $login = 0;
    public $timezone = '';
    public $language = '';

    /**
     * Renders mail
     */
    public function renderMail($value, array $options)
    {
        return '<a href="mailto:' . check_plain($value) . '" title="' . t("Send e-mail") . '">' . check_plain($value) . '</a>';
    }

    /**
     * Renders created
     */
    public function renderCreated($value, array $options)
    {
        return format_interval(time() - $value);
    }

    /**
     * Renders changed
     */
    public function renderChanged($value, array $options)
    {
        return format_interval(time() - $value);
    }

    /**
     * Renders login
     */
    public function renderLogin($value, array $options)
    {
        return format_interval(time() - $value);
    }

    /**
     * Renders access
     */
    public function renderAccess($value, array $options)
    {
        return format_interval(time() - $value);
    }

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
    protected function getDisplayOptions()
    {
        return [
            'templates' => [
                'default' => 'module:udashboard:views/Page/page-dynamic-table.html.twig',
            ],
            'view_type' => TwigView::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDatasource()
    {
        return $this->datasource;
    }
}
