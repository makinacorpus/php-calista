<?php

namespace MakinaCorpus\Dashboard\Page;

use MakinaCorpus\Dashboard\Datasource\InputDefinition;
use MakinaCorpus\Dashboard\Datasource\DatasourceInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Creates a page builder using configuration
 *
 * This is a very basic implementation, it will need some work.
 */
class ConfigPageDefinition implements PageDefinitionInterface
{
    use ContainerAwareTrait;

    private $datasource;
    private $inputDefinition;
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
        $this->datasource = $this->resolveDatasource();

        // Resolve query/datasource configuration from array
        if ($this->definition['configuration']) {
            if ($this->definition['configuration'] instanceof InputDefinition) {
                $this->inputDefinition = $this->definition['configuration'];
            } else {
                $this->inputDefinition = new InputDefinition($this->datasource, $this->definition['configuration']);
            }
        } else {
            $this->inputDefinition = new InputDefinition($this->datasource);
        }
    }



    /**
     * Create configuration
     *
     * @param mixed[] $options = []
     *   Options overrides
     *
     * @return InputDefinition
     */
    public function createInputDefinition(array $options = [])
    {
        return $this->inputDefinition;
    }

    /**
     * Find datasource from configuration
     *
     * @return DatasourceInterface
     */
    private function resolveDatasource()
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
     * @param InputDefinition $inputDefinition
     * @param Request $request
     */
    public function build(PageBuilder $builder, InputDefinition $inputDefinition, Request $request)
    {
        $builder
            ->setDatasource($this->datasource)
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
