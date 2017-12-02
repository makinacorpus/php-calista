<?php

namespace MakinaCorpus\Calista\Action;

/**
 * Implementation that sets up environment information for you, leaving
 * you free of the need to configure a complex service.
 */
abstract class AbstractActionProvider implements ActionProviderInterface
{
    use AuthorizationActionProviderTrait;
}
