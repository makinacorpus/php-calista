<?php


namespace MakinaCorpus\Drupal\Dashboard\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

class SelectionFormType extends AbstractType
{
    /**
     * {@inheritDoc}
     *
     * Create a simple form with selection checkbox
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('id', HiddenType::class);
        $builder->add('selected', CheckboxType::class, [
            'required' => false,
        ]);
    }
}
