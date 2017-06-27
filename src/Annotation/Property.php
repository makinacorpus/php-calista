<?php

namespace MakinaCorpus\Calista\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Property
{
    private $values;

    /**
     * This will accept any value, but as long as the OptionResolver is in use
     * for parsing display options, we're good to go.
     *
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->values;
    }
}
