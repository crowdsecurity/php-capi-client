<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * The Watcher configuration.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('config');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->children()
            ->enumNode('env')
                ->values(
                    [
                        Constants::ENV_DEV,
                        Constants::ENV_PROD,
                    ]
                )
                ->defaultValue(Constants::ENV_DEV)
            ->end()
            ->scalarNode('machine_id_prefix')
                ->validate()
                ->ifTrue(function (string $value) {
                    if (!empty($value)) {
                        return strlen($value) > 16 || 1 !== preg_match('#^[a-z0-9]+$#', $value);
                    }

                    return false;
                })
                ->thenInvalid('Invalid machine id prefix. Length must be <= 16. Allowed chars are a-z0-9')
                ->end()
            ->end()
            ->scalarNode('user_agent_suffix')
                ->validate()
                ->ifTrue(function (string $value) {
                    if (!empty($value)) {
                        return strlen($value) > 16 || 1 !== preg_match('#^[A-Za-z0-9]+$#', $value);
                    }

                    return false;
                })
                ->thenInvalid('Invalid user agent suffix. Length must be <= 16. Allowed chars are A-Za-z0-9')
                ->end()
            ->end()
            ->arrayNode('scenarios')->isRequired()->cannotBeEmpty()
                ->validate()
                ->ifArray()
                ->then(function (array $value) {
                    return array_values(array_unique($value));
                })
                ->end()
                ->scalarPrototype()->cannotBeEmpty()
                ->end()
            ->end()
        ->end()
        ;

        return $treeBuilder;
    }
}
