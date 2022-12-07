<?php

namespace Sherlockode\SonataSortableBundle\Twig;

use Sherlockode\SonataSortableBundle\Manager\SortableManager;
use Twig\Extension\RuntimeExtensionInterface;

class SortableRuntime implements RuntimeExtensionInterface
{
    /**
     * @param SortableManager $sortableManager
     */
    public function __construct(private readonly SortableManager $sortableManager)
    {
    }

    /**
     * @param object $object
     * @param string $positionProperty
     *
     * @return int
     */
    public function getFirstObjectPosition(object $object, string $positionProperty): int
    {
        return $this->sortableManager->getFirstPosition(get_class($object), $positionProperty);
    }

    /**
     * @param object $object
     * @param string $positionProperty
     *
     * @return int
     */
    public function getLastObjectPosition(object $object, string $positionProperty): int
    {
        return $this->sortableManager->getLastPosition(get_class($object), $positionProperty);
    }
}
