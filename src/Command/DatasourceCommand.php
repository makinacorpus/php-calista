<?php

namespace MakinaCorpus\Calista\Command;

use MakinaCorpus\Calista\Datasource\DatasourceInterface;
use MakinaCorpus\Calista\DependencyInjection\ViewFactory;
use MakinaCorpus\Calista\Error\CalistaError;
use MakinaCorpus\Drupal\Sf\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command class to import EDMC data: cities, contracts, contact points, ...
 *
 * @todo remove all Drupal variable usage with something better.
 */
class DatasourceCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('calista:datasource');
        $this->setDescription("Introspect datasource");
        $this->setDefinition([
            new InputArgument('action', InputArgument::REQUIRED, "One of: info, list, filter, sort"),
            new InputArgument('datasource', InputArgument::OPTIONAL, "Datasource class, identifier or service identifer, required for all other actions than 'list'"),
        ]);
    }

    /**
     * Set view factory
     *
     * @return ViewFactory
     */
    private function getViewFactory()
    {
        return $this->getContainer()->get('calista.view_factory');
    }

    /**
     * List available datasources
     */
    private function listAction(InputInterface $input, OutputInterface $output)
    {
        $index = $this->getViewFactory()->listDatasources();

        if (!$input) {
            $output->writeln('<info>there is no defined datasource</info>');
            return;
        }

        $table = new Table($output);
        $table->setHeaders(['identifier', 'service', 'datasource/item classes']);
        foreach ($index as $id => $data) {
            try {
                $datasource = $this->getViewFactory()->getDatasource($id);
                $itemClass = $datasource->getItemClass();
            } catch (CalistaError $e) {
                $itemClass = '<error>broken</error>';
            }
            $row = [$id, $data['service'], $data['class']."\n".$itemClass];
            $table->addRow($row);
        }
        $table->render();
    }

    /**
     * Render boolean as yes or no
     */
    private function renderYesOrNo($value)
    {
        if ($value) {
            return 'yes';
        }
        return 'no';
    }

    /**
     * Display datasource available filters
     */
    private function infoAction(InputInterface $input, OutputInterface $output, DatasourceInterface $datasource)
    {
        $table = new Table($output);
        $table->addRow(["datasource class", get_class($datasource)]);
        $table->addRow(["item class", $datasource->getItemClass()]);
        $table->addRow(["can stream data", $this->renderYesOrNo($datasource->supportsStreaming())]);
        $table->addRow(["supports pagination", $this->renderYesOrNo($datasource->supportsPagination())]);
        $table->addRow(["supports fulltext search", $this->renderYesOrNo($datasource->supportsFulltextSearch())]);
        $table->addRow(["filter count", count($datasource->getFilters())]);
        $table->addRow(["sort count", count($datasource->getSorts())]);
        $table->render();
    }

    /**
     * Display datasource available filters
     */
    private function filterAction(InputInterface $input, OutputInterface $output, DatasourceInterface $datasource)
    {
        $filters = $datasource->getFilters();

        if (!$filters) {
            $output->writeln('<info>datasource has no filters</info>');
            return;
        }

        $table = new Table($output);
        $table->setHeaders(['name', 'label', 'safe', 'choices']);
        /** @var \MakinaCorpus\Calista\Datasource\Filter $filter */
        foreach ($filters as $filter) {

            if ($filter->count()) {
                $choices = [];
                foreach ($filter->getChoicesMap() as $value => $label) {
                    $choices[] = $value.': '.$label;
                }
                $choices = implode("\n", $choices);
            } else {
                $choices = "arbitrary";
            }

            $table->addRow([
                $filter->getField(),
                $filter->getTitle(),
                $filter->isSafe() ? "yes" : 'no',
                $choices
            ]);
        }
        $table->render();
    }

    /**
     * Display datasource available sorts
     */
    private function sortAction(InputInterface $input, OutputInterface $output, DatasourceInterface $datasource)
    {
        $sorts = $datasource->getSorts();

        if (!$sorts) {
            $output->writeln('<info>datasource has no sort</info>');
            return;
        }

        $table = new Table($output);
        $table->setHeaders(['name', 'label']);
        foreach ($sorts as $name => $label) {
            $table->addRow([$name, $label]);
        }
        $table->render();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $action = $input->getArgument('action');

        if ('list' === $action) {
            return $this->listAction($input, $output);
        }

        if (!$input->hasArgument('datasource')) {
            $output->writeln("<error>datasource argument is required</error>");
            return -1;
        }

        $datasourceId = $input->getArgument('datasource');
        $datasource = $this->getViewFactory()->getDatasource($datasourceId);

        switch ($action) {

            case 'info':
                return $this->infoAction($input, $output, $datasource);

            case 'filter':
                return $this->filterAction($input, $output, $datasource);

            case 'sort':
                return $this->sortAction($input, $output, $datasource);

            default:
                $output->writeln(sprintf("unknown action '%s'", $action));
                return -1;
        }
    }
}
