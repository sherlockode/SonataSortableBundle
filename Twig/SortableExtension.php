<?php

namespace Sherlockode\SonataSortableBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SortableExtension extends AbstractExtension
{
    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('first_object_position', [SortableRuntime::class, 'getFirstObjectPosition']),
            new TwigFunction('last_object_position', [SortableRuntime::class, 'getLastObjectPosition']),
        ];
    }
}
