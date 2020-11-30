<?php

namespace Tests\AppBundle\EventListener;

use AppBundle\AppBundle;
use AppBundle\Entity\Sensor;
use AppBundle\EventListener\CreateUpdateSetter;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class CreateUpdateSetterTest
 * @package Tests\AppBundle\Repository
 */
class CreateUpdateSetterTest extends WebTestCase
{
    public function testFunctionExist()
    {
        $setter = new CreateUpdateSetter();
        $this->assertEquals(
            [
                Events::prePersist,
                Events::preUpdate,
            ],
            $setter->getSubscribedEvents()
        );
        $this->assertTrue(method_exists($setter, 'prePersist'));
        $this->assertTrue(method_exists($setter, 'preUpdate'));
    }

    public function testPrePersist()
    {
        $setter = new CreateUpdateSetter();
        $object = new Sensor();
        /** @var ObjectManager $emMock */
        $emMock = $this->createMock(ObjectManager::class);
        $args = new LifecycleEventArgs($object, $emMock);

        $setter->prePersist($args);
        $this->assertEmpty($object->getUpdated());
        $this->assertEquals(
            (new \DateTime())->format(AppBundle::DATETIME_FORMAT),
            $object->getCreated()->format(AppBundle::DATETIME_FORMAT)
        );
    }

    public function testPreUpdate()
    {
        $created = new \DateTime('2020-11-11');
        $setter = new CreateUpdateSetter();
        $object = new Sensor();
        $object->setCreated($created);
        /** @var ObjectManager $emMock */
        $emMock = $this->createMock(ObjectManager::class);
        $args = new LifecycleEventArgs($object, $emMock);

        $setter->preUpdate($args);
        $this->assertEquals($created, $object->getCreated());
        $this->assertEquals(
            (new \DateTime())->format(AppBundle::DATETIME_FORMAT),
            $object->getUpdated()->format(AppBundle::DATETIME_FORMAT)
        );
    }
}
