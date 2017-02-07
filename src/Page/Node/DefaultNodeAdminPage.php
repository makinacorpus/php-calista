<?php

namespace MakinaCorpus\Drupal\Dashboard\Page\Node;

use Drupal\Core\Session\AccountInterface;
use MakinaCorpus\Drupal\Dashboard\Page\DatasourceInterface;
use MakinaCorpus\Drupal\Dashboard\Page\PageBuilder;
use MakinaCorpus\Drupal\Dashboard\Page\PageTypeInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Default node admin page implementation, suitable for most use cases
 */
class DefaultNodeAdminPage implements PageTypeInterface
{
    private $datasource;
    private $queryFilter;
    private $permission;

    /**
     * Default constructor
     *
     * @param DatasourceInterface $datasource
     * @param string $permission
     * @param mixed[] $queryFilter
     */
    public function __construct(
        DatasourceInterface $datasource,
        $permission,
        array $queryFilter = []
    ) {
        $this->datasource = $datasource;
        $this->permission = $permission;
        $this->queryFilter = $queryFilter;
    }

    /**
     * Get datasource
     *
     * @return DatasourceInterface
     */
    final protected function getDatasource()
    {
        return $this->datasource;
    }

    /**
     * Get default query filters
     *
     * @return array
     */
    final protected function getQueryFilters()
    {
        return $this->queryFilter ? $this->queryFilter : [];
    }

    /**
     * {@inheritdoc}
     */
    public function userIsGranted(AccountInterface $account)
    {
        return $account->hasPermission($this->permission);
    }

    /**
     * For implementors, attach to this function to set default filters
     * for your admin screen
     *
     * @param PageBuilder $builder
     */
    protected function prepareDefaultFilters(PageBuilder $builder)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function build(PageBuilder $builder, Request $request)
    {
        $builder
            ->setAllowedTemplates([
                'grid' => 'module:udashboard:views/Page/page-grid.html.twig',
                'table' => 'module:udashboard:views/Page/page.html.twig',
            ])
            ->setDefaultDisplay('table')
            ->setDatasource($this->getDatasource())
        ;

        foreach ($this->getQueryFilters() as $name => $value) {
            $builder->addBaseQueryParameter($name, $value);
        }
    }
}
