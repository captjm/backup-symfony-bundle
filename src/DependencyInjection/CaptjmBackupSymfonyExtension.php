<?php

namespace Captjm\BackupSymfonyBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

class CaptjmBackupSymfonyExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
     //   $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
     //   $loader->load('routes.yaml');
    }
}