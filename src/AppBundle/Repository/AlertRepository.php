<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Sensor;
use Doctrine\ORM\QueryBuilder;

/**
 * Class AlertRepository
 * @package AppBundle\Repository
 */
class AlertRepository extends \Doctrine\ORM\EntityRepository
{
    const LIST_FIELDS = [
        'eq' => [
            'operator' => '=',
            'fields' => ['sensor'],
        ],
        'gte' => [
            'operator' => '>=',
            'fields' => ['start'],
        ],
        'lte' => [
            'operator' => '<=',
            'fields' => ['end'],
        ],
    ];

    const DATETIME_FIELDS = ['start', 'end'];

    /**
     * @param Sensor $sensor
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @param array $orderBy
     * @return array
     */
    public function listAlerts(
        Sensor $sensor,
        array $filters = [],
        int $limit = 10,
        int $offset = 0,
        array $orderBy  = ['created' => 'DESC']
    ): array {
        $filters['sensor'] = $sensor;
        $alias = 'a';
        $queryBuilder = $this->buildQueryBuilderByFilters($filters, $alias);

        foreach ($orderBy as $field => $order) {
            $queryBuilder->orderBy($alias . '.' . $field, $order);
        }

        $queryBuilder->setMaxResults($limit)
            ->setFirstResult($offset);

        $alerts = $queryBuilder->getQuery()->getResult();

        return $alerts;
    }

    /**
     * @param array $filters
     * @param string $alias
     * @return QueryBuilder
     */
    private function buildQueryBuilderByFilters(array $filters, string $alias)
    {
        $datetimeFields = array_flip(self::DATETIME_FIELDS);
        $builder = $this->createQueryBuilder($alias);

        foreach (self::LIST_FIELDS as $setting) {
            $fields = array_intersect_key($filters, array_flip($setting['fields']));

            foreach ($fields as $field => $value) {
                if ($value) {
                    if (isset($datetimeFields[$field])) {
                        $value = new \DateTime($value);
                    }
                    $builder->andWhere(sprintf('%s.%s %s :%s', $alias, $field, $setting['operator'], $field))
                        ->setParameter($field, $value);
                }
            }
        }

        return $builder;
    }
}
