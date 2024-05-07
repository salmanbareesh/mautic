<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Model;

use Doctrine\DBAL\Exception;

/**
 * Interface MapModelInterface.
 *
 * @template T of object
 */
interface MapModelInterface
{
    /**
     * @param T $entity
     *
     * @return array<string, array<int, array<string, int|string>>>
     *
     * @throws Exception
     */
    public function getCountryStats($entity, \DateTimeImmutable $dateFrom, \DateTimeImmutable $dateTo, bool $includeVariants = false): array;

    /**
     * @param int|string|null $id
     *
     * @phpstan-ignore-next-line
     */
    public function getEntity($id = null): ?object;
}