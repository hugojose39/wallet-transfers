<?php

declare(strict_types=1);

use Hyperf\Di\ClassLoader;
use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSourceFactory;
use Hyperf\Contract\ApplicationInterface;
use Psr\Container\ContainerInterface;

$container = new Container((new DefinitionSourceFactory())());

if (! $container instanceof ContainerInterface) {
    throw new RuntimeException('The dependency injection container is invalid.');
}

return $container->get(ApplicationInterface::class);
