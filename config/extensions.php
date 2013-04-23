<?php

use Camspiers\DependencyInjection\SharedContainerFactory;
use Heystack\Subsystem\Deals\DependencyInjection;

SharedContainerFactory::addExtension(new DependencyInjection\ContainerExtension());
