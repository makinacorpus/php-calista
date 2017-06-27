<?php

namespace MakinaCorpus\Calista\DependencyInjection;

use MakinaCorpus\Calista\Datasource\InputDefinition;
use MakinaCorpus\Calista\Error\ConfigurationError;
use MakinaCorpus\Calista\View\ViewDefinition;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Uses a raw config array
 */
class ConfigPageDefinition implements PageDefinitionInterface, ContainerAwareInterface
{
    use ServiceTrait;

    private $config;
    private $container;
    private $datasource;

    /**
     * Default constructor
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        if (empty($config['datasource'])) {
            throw new ConfigurationError("datasource is missing");
        }
        if (empty($config['view']['view_type'])) {
            throw new ConfigurationError("view:view_type is missing");
        }

        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    final public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getInputDefinition(array $options = [])
    {
        if (isset($this->config['input'])) {
            $options += $this->config['input'];
        }

        return new InputDefinition($this->getDatasource(), $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getViewDefinition()
    {
        return new ViewDefinition($this->config['view']);
    }

    /**
     * {@inheritdoc}
     */
    public function getDatasource()
    {
        if (!$this->datasource) {
            if (!$this->container) {
                throw new ConfigurationError("container is missing");
            }

            /** @var \MakinaCorpus\Calista\DependencyInjection\ViewFactory $registry */
            try {
                $registry = $this->container->get('calista.view_factory');
                $this->datasource = $registry->getDatasource($this->config['datasource']);
            } catch (ServiceNotFoundException $e) {
                throw new ConfigurationError(sprintf("could not find datasource '%s'", $this->config['datasource']), null, $e);
            }
        }

        return $this->datasource;
    }
}
