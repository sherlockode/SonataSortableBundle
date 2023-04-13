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

    /**
     * @param object $object
     * @param string $property
     * @param string $direction
     */
    public function setPosition(object $object, string $property, string $direction): void
    {
        if ('top' === $direction) {
            $this->top($object, $property);
        }

        if ('up' === $direction) {
            $this->up($object, $property);
        }

        if ('down' === $direction) {
            $this->down($object, $property);
        }

        if ('bottom' === $direction) {
            $this->bottom($object, $property);
        }
    }

    /**
     * @param object $object
     * @param string $property
     */
    private function top(object $object, string $property): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $firstPosition = $this->getFirstPosition(get_class($object), $property);
        $currentPosition = $propertyAccessor->getValue($object, $property);

        if ($currentPosition <= $firstPosition) {
            return;
        }

        $this->em
            ->createQuery(sprintf(
                'UPDATE %s o SET o.%s = o.%s + 1 WHERE o.%s < :currentPosition',
                get_class($object),
                $property,
                $property,
                $property
            ))
            ->setParameter('currentPosition', $currentPosition)
            ->execute();
        $propertyAccessor->setValue($object, $property, $firstPosition);
    }

    /**
     * @param object $object
     * @param string $property
     */
    private function up(object $object, string $property): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $firstPosition = $this->getFirstPosition(get_class($object), $property);
        $currentPosition = $propertyAccessor->getValue($object, $property);

        if ($currentPosition <= $firstPosition) {
            return;
        }

        $previousPosition = 0;
        $previousRecords = $this->getPreviousRecords($object, $property);

        foreach ($previousRecords as $previousRecord) {
            $previousPosition = $propertyAccessor->getValue($previousRecord, $property);
            $propertyAccessor->setValue($previousRecord, $property, $currentPosition);
        }

        $propertyAccessor->setValue($object, $property, $previousPosition);
    }

    /**
     * @param object $object
     * @param string $property
     */
    private function down(object $object, string $property): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $lastPosition = $this->getLastPosition(get_class($object), $property);
        $currentPosition = $propertyAccessor->getValue($object, $property);

        if ($currentPosition >= $lastPosition) {
            return;
        }

        $nextPosition = $lastPosition;
        $nextRecords = $this->getNextRecords($object, $property);

        foreach ($nextRecords as $nextRecord) {
            $nextPosition = $propertyAccessor->getValue($nextRecord, $property);
            $propertyAccessor->setValue($nextRecord, $property, $currentPosition);
        }

        $propertyAccessor->setValue($object, $property, $nextPosition);
    }

    /**
     * @param object $object
     * @param string $property
     */
    private function bottom(object $object, string $property): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $lastPosition = $this->getLastPosition(get_class($object), $property);
        $currentPosition = $propertyAccessor->getValue($object, $property);

        if ($currentPosition >= $lastPosition) {
            return;
        }

        $this->em
            ->createQuery(sprintf(
                'UPDATE %s o SET o.%s = o.%s - 1 WHERE o.%s > :currentPosition',
                get_class($object),
                $property,
                $property,
                $property
            ))
            ->setParameter('currentPosition', $currentPosition)
            ->execute();
        $propertyAccessor->setValue($object, $property, $lastPosition);
    }

    /**
     * @param object $object
     * @param string $property
     *
     * @return array
     */
    private function getPreviousRecords(object $object, string $property): array
    {
        $repository = $this->em->getRepository(get_class($object));
        $subQuery = $this->em->createQueryBuilder()
            ->select(sprintf('MAX(o2.%s)', $property))
            ->from(get_class($object), 'o2')
            ->andWhere(sprintf('o2.%s < :currentPosition', $property));

        return $repository->createQueryBuilder('o1')
            ->andWhere(sprintf('o1.%s = (%s)', $property, $subQuery->getDQL()))
            ->setParameter('currentPosition', $this->getObjectPosition($object, $property))
            ->getQuery()
            ->getResult();
    }

    /**
     * @param object $object
     * @param string $property
     *
     * @return array
     */
    private function getNextRecords(object $object, string $property): array
    {
        $repository = $this->em->getRepository(get_class($object));
        $subQuery = $this->em->createQueryBuilder()
            ->select(sprintf('MIN(o2.%s)', $property))
            ->from(get_class($object), 'o2')
            ->andWhere(sprintf('o2.%s > :currentPosition', $property));

        return $repository->createQueryBuilder('o1')
            ->andWhere(sprintf('o1.%s = (%s)', $property, $subQuery->getDQL()))
            ->setParameter('currentPosition', $this->getObjectPosition($object, $property))
            ->getQuery()
            ->getResult();
    }

    /**
     * @param object $object
     * @param string $property
     *
     * @return int
     */
    private function getObjectPosition(object $object, string $property): int
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        return $propertyAccessor->getValue($object, $property);
    }
}
