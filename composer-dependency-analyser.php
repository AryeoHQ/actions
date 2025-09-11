<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;

$config = new Configuration;

return $config
    ->addPathToExclude(__DIR__.'/src/Tooling');
