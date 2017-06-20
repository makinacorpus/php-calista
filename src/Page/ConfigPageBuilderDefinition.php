<?php

namespace MakinaCorpus\Dashboard\Page;

use MakinaCorpus\Dashboard\Datasource\Configuration;
use MakinaCorpus\Dashboard\Datasource\DatasourceInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Creates a page builder using configuration
 *
 * This is a very basic implementation, it will need some work.
 */
class ConfigPageBuilderDefinition implements PageDefinitionInterface
{
    use ContainerAwareTrait;

    private $configuration;
    private $definition;

    /**
     * Default constructor
     *
     * @param array $definition
     */
    public function __construct(array $definition = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->definition = $resolver->resolve($definition);

        // Resolve query/datasource configuration from array
        if ($this->definition['configuration']) {
            if ($this->definition['configuration'] instanceof Configuration) {
                $this->configuration = $this->definition['configuration'];
            } else {
                $this->configuration = new Configuration($this->definition['configuration']);
            }
        } else {
            $this->configuration = new Configuration();
        }
    }

    /**
     * Configuration option resolver
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'base_query'        => [],
            'configuration'     => null,
            'default_display'   => 'default',
            'disabled_sorts'    => [],
            'disabled_filters'  => [],
            'enabled_filters'   => [],
            'show_filters'      => false,
            'show_pager'        => false,
            'show_search'       => false,
            'show_sort'         => false,
            'templates'         => [],
        ]);

        $resolver->setRequired('datasource', 'templates');

        $resolver->setAllowedTypes('base_query', ['array']);
        $resolver->setAllowedTypes('configuration', ['array', Configuration::class]);
        $resolver->setAllowedTypes('datasource', ['string', DatasourceInterface::class]);
        $resolver->setAllowedTypes('default_display', ['string']);
        $resolver->setAllowedTypes('disabled_sorts', ['array']);
        $resolver->setAllowedTypes('enabled_filters', ['array']);
        $resolver->setAllowedTypes('disabled_filters', ['array']);
        $resolver->setAllowedTypes('show_filters', ['numeric', 'bool']);
        $resolver->setAllowedTypes('show_pager', ['numeric', 'bool']);
        $resolver->setAllowedTypes('show_search', ['numeric', 'bool']);
        $resolver->setAllowedTypes('show_sort', ['numeric', 'bool']);
        $resolver->setAllowedTypes('templates', ['array']);
    }

    /**
     * Create configuration
     *
     * @param mixed[] $options = []
     *   Options overrides
     *
     * @return Configuration
     */
    public function createConfiguration(array $options = [])
    {
        return $this->configuration;
    }

    /**
     * Find datasource from configuration
     *
     * @return DatasourceInterface
     */
    private function findDatasource()
    {
        $datasourceOrId = $this->definition['datasource'];

        if ($datasourceOrId instanceof DatasourceInterface) {
            return $datasourceOrId;
        }

        if (!$this->container) {
            throw new \LogicException("container is you set, did you forget to call setContainer()?");
        }
        if (!$this->container->has($datasourceOrId)) {
            throw new \LogicException(sprintf("container has no service '%s', unable to find datasource", $datasourceOrId));
        }

        $datasource = $this->container->get($datasourceOrId);
        if (!$datasource instanceof DatasourceInterface) {
            throw new \LogicException(sprintf("container has a service '%s', but it does not implement %s", $datasourceOrId, DatasourceInterface::class));
        }

        return $datasource;
    }

    /**
     * Build the page parameters
     *
     * @param PageBuilder $builder
     * @param Configuration $configuration
     * @param Request $request
     */
    public function build(PageBuilder $builder, Configuration $configuration, Request $request)
    {
        $datasource = $this->findDatasource();

        $builder
            ->setDatasource($datasource)
            ->setBaseQuery($this->definition['base_query'])
            ->setAllowedTemplates($this->definition['templates'])
            ->setDefaultDisplay($this->definition['default_display'])
        ;

        if ($this->definition['show_filters']) {
            $builder->showFilters();
        } else {
            $builder->hideFilters();
        }

        if ($this->definition['show_pager']) {
            $builder->showPager();
        } else {
            $builder->hidePager();
        }

        if ($this->definition['show_search']) {
            $builder->showSearch();
        } else {
            $builder->hideSearch();
        }

        if ($this->definition['show_sort']) {
            $builder->showSort();
        } else {
            $builder->hideSort();
        }

        if ($this->definition['disabled_sorts']) {
            foreach ($this->definition['disabled_sorts'] as $field) {
                $builder->disableSort($field);
            }
        }

        // Enabled filters are masked by disabled filters, in most case using
        // the two at the same time seems rather stupid.
        if ($this->definition['enabled_filters']) {
            foreach ($this->definition['enabled_filters'] as $field) {
                $builder->enableFilter($field);
            }
        }
        if ($this->definition['disabled_filters']) {
            foreach ($this->definition['disabled_filters'] as $field) {
                $builder->disableFilter($field);
            }
        }
    }
}
