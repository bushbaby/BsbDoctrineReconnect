<?php

namespace BsbDoctrineReconnect;

use Zend\ModuleManager\Feature;

/**
 * Class Module
 *
 * @package BsbDoctrineReconnect
 */
class Module implements Feature\DependencyIndicatorInterface
{
    /**
     * {@inheritDoc}
     */
    public function getModuleDependencies()
    {
        return ['DoctrineModule'];
    }
}
