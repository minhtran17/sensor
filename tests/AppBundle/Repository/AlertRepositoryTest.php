<?php

namespace Tests\AppBundle\Repository;

use AppBundle\Repository\AlertRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;

/**
 * Class AlertRepositoryTest
 * @package Tests\AppBundle\Repository
 */
class AlertRepositoryTest extends AbstractTest
{
    public function testListAlerts()
    {
        $alias = 'a';
        $filters = [
            'start' => '2020-11-11T10:10:10',
            'end' => '2020-11-12T10:10:10',
            'invalid_field' => 123,
        ];
        $expected = ['expected_result'];

        $queryMock = $this->getMockBuilder(AbstractQuery::class)
            ->setMethods(['getResult'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $queryMock->expects($this->once())->method('getResult')->willReturn($expected);

        $builderMock = $this->getMockBuilder(QueryBuilder::class)
            ->setMethods(['getQuery', 'andWhere', 'setParameter', 'orderBy', 'setMaxResults', 'setFirstResult'])
            ->disableOriginalConstructor()
            ->getMock();
        $builderMock->expects($this->once())->method('getQuery')->willReturn($queryMock);
        $builderMock->setParameters([]);
        $builderMock->expects($this->exactly(3))
            ->method('andWhere')
            ->withConsecutive(
                [sprintf('%s.sensor = :sensor', $alias)],
                [sprintf('%s.start >= :start', $alias)],
                [sprintf('%s.end <= :end', $alias)]
            )
            ->willReturnSelf();
        $builderMock->expects($this->exactly(3))
            ->method('setParameter')
            ->withConsecutive(
                ['sensor', $this->sensor],
                ['start', new \DateTime($filters['start'])],
                ['end', new \DateTime($filters['end'])]
            )
            ->willReturnSelf();
        $builderMock->expects($this->once())
            ->method('orderBy')
            ->with($alias . '.created', 'DESC')
            ->willReturnSelf();
        $builderMock->expects($this->once())
            ->method('setMaxResults')
            ->with(10)
            ->willReturnSelf();
        $builderMock->expects($this->once())
            ->method('setFirstResult')
            ->with(0)
            ->willReturnSelf();

        $mock = $this->getMockBuilder(AlertRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['createQueryBuilder'])
            ->getMock();
        $mock->expects($this->once())->method('createQueryBuilder')->willReturn($builderMock);

        $result = $mock->listAlerts($this->sensor, $filters);

        $this->assertEquals($expected, $result);
    }
}
