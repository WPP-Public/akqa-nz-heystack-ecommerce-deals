<?php

use Camspiers\DependencyInjection\SharedContainerFactory;
use Heystack\Deals\DependencyInjection;

SharedContainerFactory::addExtension(new DependencyInjection\ContainerExtension());
