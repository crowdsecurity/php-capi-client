<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\Configuration;

use CrowdSec\CapiClient\Signal as SignalClass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * The Signal configuration.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class Signal implements ConfigurationInterface
{
    public const SCENARIO_REGEX = '#^([a-zA-Z0-9_-]+)\/([a-zA-Z0-9_-]+)$#';

    public const ISO8601_REGEX = '#^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(.\d{6})?Z$#';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('signalConfig');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->children()
            ->scalarNode('scenario_trust')->defaultValue(SignalClass::TRUST_MANUAL)->end()
            ->scalarNode('scenario_hash')
                ->isRequired()->defaultValue('')
            ->end()
            ->scalarNode('scenario')
                ->isRequired()->cannotBeEmpty()
                ->validate()
                ->ifTrue(function (string $value) {
                    return 1 !== preg_match(self::SCENARIO_REGEX, $value);
                })
                ->thenInvalid('Invalid scenario. Must match with ' . self::SCENARIO_REGEX . ' regex')
                ->end()
            ->end()
            ->integerNode('alert_id')->min(0)->end()
            ->scalarNode('created_at')
                ->cannotBeEmpty()
                ->validate()
                ->ifTrue(function (string $value) {
                    return 1 !== preg_match(self::ISO8601_REGEX, $value);
                })
                ->thenInvalid('Invalid created_at. Must match with ' . self::ISO8601_REGEX . ' regex')
                ->end()
            ->end()
            ->scalarNode('machine_id')->cannotBeEmpty()->end()
            ->scalarNode('scenario_version')->isRequired()->defaultValue('')->end()
            ->scalarNode('message')->isRequired()->defaultValue('')->end()
            ->scalarNode('start_at')
                ->isRequired()->cannotBeEmpty()
                ->validate()
                ->ifTrue(function (string $value) {
                    return 1 !== preg_match(self::ISO8601_REGEX, $value);
                })
                ->thenInvalid('Invalid start_at. Must match with ' . self::ISO8601_REGEX . ' regex')
                ->end()
            ->end()
            ->scalarNode('stop_at')
                ->isRequired()->cannotBeEmpty()
                ->validate()
                ->ifTrue(function (string $value) {
                    return 1 !== preg_match(self::ISO8601_REGEX, $value);
                })
                ->thenInvalid('Invalid stop_at. Must match with ' . self::ISO8601_REGEX . ' regex')
                ->end()
            ->end()
        ->end()
        ;

        return $treeBuilder;
    }
}
