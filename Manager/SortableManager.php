<?php

namespace Sherlockode\SonataSortableBundle\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Component\PropertyAccess\PropertyAccess;

class SortableManager
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /**
     * @param object $object
     * @param string $positionProperty
     * @param string $direction
     *
     * @return int
     */
    public function getPosition(object $object, string $positionProperty, string $direction): int
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $currentPosition = $propertyAccessor->getValue($object, $positionProperty);
        $lastPosition = $this->getLastPosition(get_class($object), $positionProperty);

        if ('top' === $direction) {
            return 0;
        }

        if ('bottom' === $direction && $currentPosition < $lastPosition) {
            return $lastPosition;
        }

        if ('up' === $direction && $currentPosition > 0) {
            return $currentPosition - 1;
        }

        if ('down' === $direction && $currentPosition < $lastPosition) {
            return $currentPosition + 1;
        }

        return $currentPosition;
    }

    /**
     * @param string $className
     * @param string $positionProperty
     *
     * @return int
     */
    public function getFirstPosition(string $className, string $positionProperty): int
    {
        try {
            $position = $this->em->createQueryBuilder()
                ->select(sprintf('MIN(o.%s)', $positionProperty))
                ->from($className, 'o')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            return 0;
        }

        return $position ?? 0;
    }

    /**
     * @param string $className
     * @param string $positionProperty
     *
     * @return int
     */
    public function getLastPosition(string $className, string $positionProperty): int
    {
        try {
            $position = $this->em->createQueryBuilder()
                ->select(sprintf('MAX(o.%s)', $positionProperty))
                ->from($className, 'o')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            return 0;
        }

        return $position ?? 0;
    }
}
