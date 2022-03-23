<?php

namespace Draw\Bundle\FrameworkExtraBundle\DependencyInjection;

use Draw\Component\Process\ProcessFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('draw_framework_extra');

        $node = $treeBuilder->getRootNode();

        $node
            ->children()
                ->append($this->createProcessNode())
            ->end();

        return $treeBuilder;
    }

    private function createProcessNode(): ArrayNodeDefinition
    {
        return (new ArrayNodeDefinition('process'))
            ->{class_exists(ProcessFactory::class) ? 'canBeDisabled' : 'canBeEnabled'}();
    }
}
