<?php

use PHPUnit\Framework\TestCase;

require_once(__DIR__ . '/../../lib/bootstrap.php');
require_once(__DIR__ . '/../helper.php');

class BaseObjectTest extends TestCase {
    protected \Gazelle\User $object;

    public function testBaseObject(): void {
        $object = Helper::makeUser('bo.' . randomString(6), 'base object');

        $this->assertFalse($object->dirty(), 'base-object-initial');
        $this->assertNull($object->field('phpunit'), 'base-object-no-field');
        $this->assertInstanceOf($object::class, $object->setField('phpunit', 'value'), 'base-object-set-field');
        $this->assertTrue($object->dirty(), 'base-object-dirty');
        $this->assertEquals('value', $object->field('phpunit'), 'base-object-has-field');

        $this->assertEquals('value', $object->clearField('phpunit'), 'base-object-clear-field');
        $this->assertFalse($object->dirty(), 'base-object-final');
        $this->assertNull($object->field('phpunit'), 'base-object-no-final');

        // this is probably better placed in UserTest, but since we are already using a User...
        $this->assertTrue($object->setField('created', '2020-02-02')->modify(), 'base-object-primary-modify');
        $date = $object->created();
        $this->assertTrue($object->setFieldNow('created')->modify(), 'base-object-primary-table-now');
        $this->assertNotEquals($date, $object->created(), 'base-object-primary-remodified');
        $this->assertTrue(Helper::recentDate($object->created()), 'base-object-primary-recent');

        $this->assertTrue($object->setField('BanDate', '2020-02-02')->modify(), 'base-object-aux-modify');
        $date = $object->banDate();
        $this->assertTrue($object->setFieldNow('BanDate')->modify(), 'base-object-aux-now');
        $this->assertNotEquals($date, $object->banDate(), 'base-object-aux-remodified');
        $this->assertTrue(Helper::recentDate($object->banDate()), 'base-object-aux-recent');

        $object->remove();
    }
}
