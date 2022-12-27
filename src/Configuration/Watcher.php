<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\Configuration;

use CrowdSec\CapiClient\Constants;
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
class Watcher implements ConfigurationInterface
{
    /**
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('watcherConfig');
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
                    return 1 !== preg_match('#^[a-z0-9]{0,16}$#', $value);
                })
                ->thenInvalid('Invalid machine id prefix. Length must be <= 16. Allowed chars are a-z0-9')
                ->end()
            ->end()
            ->scalarNode('user_agent_suffix')
                ->validate()
                ->ifTrue(function (string $value) {
                    return 1 !== preg_match('#^[A-Za-z0-9]{0,16}$#', $value);
                })
                ->thenInvalid('Invalid user agent suffix. Length must be <= 16. Allowed chars are A-Za-z0-9')
                ->end()
            ->end()
            ->scalarNode('user_agent_version')
                ->validate()
                ->ifTrue(function (string $value) {
                    if (!empty($value)) {
                        return 1 !== preg_match('#^v\d{1,4}(\.\d{1,4}){2}$#', $value);
                    }

                    return true;
                })
                ->thenInvalid('Invalid user agent version. Must match vX.Y.Z format')
                ->end()
                ->defaultValue(Constants::VERSION)
            ->end()
            ->arrayNode('scenarios')->isRequired()->cannotBeEmpty()
                ->validate()
                    ->ifTrue(function (array $scenarios) {
                        foreach ($scenarios as $scenario){
                            if(1 !== preg_match('#^[A-Za-z0-9]{0,16}\/[A-Za-z0-9_-]{0,32}$#', $scenario)){
                                return true;
                            }
                        }

                        return false;
                    })
                    ->thenInvalid('Each scenario must match #^[A-Za-z0-9]{0,16}\/[A-Za-z0-9_-]{0,32}$# regex')
                ->end()
                ->validate()
                    ->ifArray()
                    ->then(function (array $value) {
                        return array_values(array_unique($value));
                    })
                ->end()
                ->scalarPrototype()->cannotBeEmpty()->end()
            ->end()
            ->integerNode('api_timeout')->defaultValue(Constants::API_TIMEOUT)->end()
        ->end()
        ;

        return $treeBuilder;
    }
}
