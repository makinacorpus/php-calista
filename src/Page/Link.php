<?php

namespace MakinaCorpus\Dashboard\Page;

/**
 * Represent a link, for templates.
 *
 * @codeCoverageIgnore
 */
class Link
{
    private $title;
    private $route;
    private $routeParameters;
    private $isActive = false;
    private $icon;

    public function __construct($title, $route, array $routeParameters = [], $isActive = false, $icon = null)
    {
        $this->title = $title;
        $this->route = $route;
        $this->routeParameters = $routeParameters;
        $this->isActive = $isActive;
        $this->icon = $icon;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getRoute()
    {
        return $this->route;
    }

    public function getRouteParameters()
    {
        return $this->routeParameters;
    }

    public function isActive()
    {
        return $this->isActive;
    }

    public function getIcon()
    {
        return $this->icon;
    }
}
