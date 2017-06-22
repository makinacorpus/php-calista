<?php

namespace MakinaCorpus\Dashboard\DependencyInjection;

/**
 * Represents a view, anything that can be displayed from datasource data
 */
interface ServiceInterface
{
    /**
     * Set identifier
     *
     * @param string $id
     */
    public function setId($id);

    /**
     * Get identifier
     *
     * @return string
     */
    public function getId();
}
