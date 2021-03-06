<?php

namespace Knp\MinibusBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Knp\MinibusBundle\Finder\ClassFinder;
use Knp\MinibusBundle\DependencyInjection\DefinitionFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Doctrine\Common\Inflector\Inflector;

/**
 * Auto register all the station in a bundle namespace. By default it will look
 * on the bundleNamespace\Station recursively and auto register the stations.
 *
 * @author David Jegat <david.jegat@gmail.com>
 */
class AutoRegisterStationPass implements CompilerPassInterface
{
    /**
     * @var Bundle $bundle
     */
    private $bundle;

    /**
     * @var ClassFinder $finder
     */
    private $finder;

    /**
     * @var DefinitionFactory $definitionFactory
     */
    private $definitionFactory;

    /**
     * @param Bundle $bundle
     * @param ClassFinder $finder
     * @param DefinitionFactory $definitionFactory
     */
    public function __construct(
        Bundle $bundle,
        ClassFinder $finder = null,
        DefinitionFactory $definitionFactory = null
    ) {
        $this->bundle            = $bundle;
        $this->finder            = $finder;
        $this->definitionFactory = $definitionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasParameter('knp_minibus.disable_station_auto_registration')) {
            return;
        }

        $reflections = $this->finder->findImplementation(
            sprintf('%s/Station', $this->bundle->getPath()),
            sprintf('%s\\Station', $this->bundle->getNamespace()),
            'Knp\Minibus\Station'
        );

        $bundleAlias = $this->deduceBundleAlias($this->bundle);

        foreach ($reflections as $reflection) {
            $definition  = $this->definitionFactory->create($reflection->getName());
            $alias       = $this->deduceStationAlias($reflection);
            $serviceName = $this->deduceServiceName($bundleAlias, $this->bundle, $reflection);

            $definition->addTag('knp_minibus.station', [
                'alias' => sprintf('%s.%s', $bundleAlias, $alias)
            ]);

            $container->setDefinition($serviceName, $definition);
        }
    }

    /**
     * @param \ReflectionClass $reflection
     *
     * @return string
     */
    private function deduceStationAlias(\ReflectionClass $reflection)
    {
        return Inflector::tableize(str_replace('Station', '', $reflection->getShortName()));
    }

    /**
     * @param Bundle $bundle
     *
     * @return string
     */
    private function deduceBundleAlias(Bundle $bundle)
    {
        if (null !== $extension = $bundle->getContainerExtension()) {
            return $extension->getAlias();
        }

        return Inflector::tableize(str_replace('Bundle', '', get_class($bundle)));
    }

    /**
     * @param string           $bundleAlias
     * @param Bundle           $bundle
     * @param \ReflectionClass $reflection
     *
     * @return string
     */
    private function deduceServiceName($bundleAlias, Bundle $bundle, \ReflectionClass $reflection)
    {
        $explodedName = explode('\\', $reflection->getName());
        $members      = [];
        $find         = false;

        foreach ($explodedName as $name) {
            if ($name === 'Station') {
                $find = true;
            }

            if (!$find) {
                continue;
            }

            $members[] = $name;
        }

        $members[count($members) - 1] = str_replace(
            'Station',
            '',
            $members[count($members) - 1]
        );

        return $bundleAlias . '.' . implode('.', array_map(function ($member) {
            return Inflector::tableize($member);
        }, $members));
    }
}
