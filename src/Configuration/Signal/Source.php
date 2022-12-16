<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\Configuration\Signal;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * The Signal source configuration.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class Source implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('signalSourceConfig');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->children()
            ->scalarNode('scope')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('value')->isRequired()->cannotBeEmpty()->end()
            ->floatNode('latitude')->end()
            ->floatNode('longitude')->end()
            ->scalarNode('cn')->end()
            ->scalarNode('as_name')->end()
            ->scalarNode('as_number')->end()
        ->end()
        ;

        return $treeBuilder;
    }
}
