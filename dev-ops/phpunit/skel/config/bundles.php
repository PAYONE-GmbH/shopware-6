<?php

declare(strict_types=1);

/**
 * This file is only required to run PhpUnit tests from within a standalone plugin
 */

$bundles = [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => [ 'all' => true ],
    Shopware\Core\Framework\Framework::class              => [ 'all' => true ],
    Shopware\Core\System\System::class                    => [ 'all' => true ],
    Shopware\Core\Content\Content::class                  => [ 'all' => true ],
    Shopware\Core\Checkout\Checkout::class                => [ 'all' => true ],
    Shopware\Core\Maintenance\Maintenance::class          => [ 'all' => true ],
    Shopware\Core\DevOps\DevOps::class                    => [ 'e2e' => true ],
    Shopware\Core\Profiling\Profiling::class              => [ 'all' => true ],
    Symfony\Bundle\DebugBundle\DebugBundle::class         => [ 'dev' => true ],
    Symfony\Bundle\MonologBundle\MonologBundle::class     => [ 'all' => true ],
    Symfony\Bundle\TwigBundle\TwigBundle::class           => [ 'all' => true ],
];

if (class_exists('\\Shopware\\Administration\\Administration')) {
    $bundles[Shopware\Administration\Administration::class] = [ 'all' => true ];
}

if (class_exists('\\Shopware\\Storefront\\Storefront')) {
    $bundles[Shopware\Storefront\Storefront::class] = [ 'all' => true ];
}

return $bundles;