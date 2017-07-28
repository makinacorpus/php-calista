<?php

namespace MakinaCorpus\Calista\Command;

use MakinaCorpus\Calista\DependencyInjection\ViewFactory;
use MakinaCorpus\Calista\Error\CalistaError;
use MakinaCorpus\Drupal\Sf\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Introspect views
 */
class ViewCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('calista:view');
        $this->setDescription("Introspect views");
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
        $index = $this->getViewFactory()->listViews();

        if (!$input) {
            $output->writeln('<info>there is no defined datasource</info>');
            return;
        }

        $table = new Table($output);
        $table->setHeaders(['identifier', 'service', 'class', 'status']);
        foreach ($index as $id => $data) {
            try {
                $this->getViewFactory()->getView($id);
                $status = 'ok';
            } catch (CalistaError $e) {
                $status = '<error>broken</error>';
            }
            $row = [$id, $data['service'], $data['class'], $status];
            $table->addRow($row);
        }
        $table->render();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->listAction($input, $output);
    }
}
