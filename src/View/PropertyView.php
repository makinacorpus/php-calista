<?php

namespace MakinaCorpus\Dashboard\View;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyInfo\Type;

/**
 * Represents a property, uses the property info to display it
 */
class PropertyView
{
    private $name = '';
    private $options = [];
    private $type;

    /**
     * Default constructor
     *
     * @param string $name
     * @param Type $type
     * @param array $options
     */
    public function __construct($name, Type $type = null, array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);

        $this->name = $name;
        $this->type = $type;
    }

    /**
     * InputDefinition option resolver
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'bool_as_int'           => false,
            'bool_value_false'      => "No",
            'bool_value_true'       => "Yes",
            'callback'              => null,
            'collection_separator'  => ', ',
            'decimal_precision'     => 2,
            'decimal_separator'     => '.',
            'label'                 => null,
            'string_ellipsis'       => true,
            'string_maxlength'      => 20,
            'thousand_separator'    => ',',
            'type'                  => null,
            'virtual'               => false,
        ]);

        $resolver->setAllowedTypes('bool_as_int', ['bool']);
        $resolver->setAllowedTypes('bool_value_false', ['string']);
        $resolver->setAllowedTypes('bool_value_true', ['string']);
        $resolver->setAllowedTypes('callback', ['null', 'callable']);
        $resolver->setAllowedTypes('collection_separator', ['null', 'string']);
        $resolver->setAllowedTypes('decimal_precision', ['null', 'int']);
        $resolver->setAllowedTypes('decimal_separator', ['null', 'string']);
        $resolver->setAllowedTypes('label', ['null', 'string']);
        $resolver->setAllowedTypes('string_ellipsis', ['null', 'bool', 'string']);
        $resolver->setAllowedTypes('string_maxlength', ['null', 'int']);
        $resolver->setAllowedTypes('thousand_separator', ['null', 'string']);
        $resolver->setAllowedTypes('type', ['null', 'string']);
        $resolver->setAllowedTypes('virtual', ['bool']);
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get label, return property name if missing
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->options['label'] ? $this->options['label'] : $this->name;
    }

    /**
     * Is this property virtual
     *
     * @return bool
     */
    public function isVirtual()
    {
        return $this->options['virtual'];
    }

    /**
     * Has this property a type
     *
     * @return bool
     */
    public function hasType()
    {
        return isset($this->type);
    }

    /**
     * Get property type
     *
     * @return Type
     */
    public function getType()
    {
        if (!$this->type) {
            // Allow graceful runtime degradation in case of erroneous template
            return new Type(Type::BUILTIN_TYPE_NULL);
        }

        return $this->type;
    }

    /**
     * Get display options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
}
