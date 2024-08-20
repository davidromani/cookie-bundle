<?php

namespace Eveltic\CookieBundle;


use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/**
 * Main bundle class for the Eveltic Cookie Bundle.
 * This bundle is responsible for registering and configuring
 * the services and parameters needed for cookie consent management.
 */
class EvelticCookieBundle extends AbstractBundle
{
    /**
     * Configure the default settings for the bundle.
     *
     * @param DefinitionConfigurator $definition
     */
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('categories')
                    ->performNoDeepMerging()
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('name')->end()
                            ->booleanNode('required')->defaultFalse()->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('domain')->defaultNull()->end()
                ->scalarNode('expiration')->defaultValue('+2 years')->end()
                ->scalarNode('theme_mode')
                    ->defaultValue('auto')
                    ->validate()
                        ->ifNotInArray(['light', 'dark', 'auto'])
                        ->thenInvalid('Invalid theme mode %s')
                    ->end()
                ->end()
                ->integerNode('version')->defaultValue(1)->end()
            ->end()
        ;
    }

    /**
     * Load services and configuration for the bundle.
     *
     * @param array $config
     * @param ContainerConfigurator $container
     * @param ContainerBuilder $builder
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->parameters()->set('eveltic_cookie.categories', $config['categories']);
        $container->parameters()->set('eveltic_cookie.domain', $config['domain'] ?? null);
        $container->parameters()->set('eveltic_cookie.expiration', $config['expiration']);
        $container->parameters()->set('eveltic_cookie.version', $config['version']);
        $container->parameters()->set('eveltic_cookie.theme_mode', $config['theme_mode']);

        $container->import(sprintf('%s/config/%s', $this->getRootPath(), 'services.yaml'));
    }

    /**
     * Prepend configuration before any other extension is loaded.
     *
     * @param ContainerConfigurator $containerConfigurator
     * @param ContainerBuilder $containerBuilder
     */
    public function prependExtension(ContainerConfigurator $containerConfigurator, ContainerBuilder $containerBuilder): void
    {
        $containerConfigurator->import(sprintf('%s/config/packages/%s', $this->getRootPath(), 'eveltic_cookie.yaml'));
    }
    
    /**
     * Get the root path of the bundle.
     *
     * @return string
     */
    public function getRootPath(): string
    {
        return dirname(__DIR__);
    }
}
