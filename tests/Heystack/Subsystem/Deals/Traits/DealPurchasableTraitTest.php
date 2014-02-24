<?php
namespace Heystack\Deals\Test;

class DealPurchasableTraitTest extends \PHPUnit_Framework_TestCase
{

    protected $trait;
    protected $identifier;

    protected function setUp()
    {
        $this->trait = $this->getObjectForTrait('Heystack\Deals\Traits\DealPurchasableTrait');
        $this->identifier = $this->getMockBuilder('Heystack\Core\Identifier\Identifier')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testIsInitiallyEmpty()
    {

        $this->assertAttributeEmpty('freeQuantities', $this->trait);

    }

    public function testSetFreeQuantity()
    {

        $this->trait->setFreeQuantity($this->identifier, 10);

        $this->assertEquals($this->trait->getFreeQuantity($this->identifier), 10);

    }

    public function testGetFreeQuantity()
    {

        $this->trait->setFreeQuantity($this->identifier, 12);

        $quantity = $this->trait->getFreeQuantity();

        $this->assertEquals($quantity, 12);

    }

    public function testGetFreeQuantityWithIdentifier()
    {

        $this->trait->setFreeQuantity($this->identifier, 12);

        $quantity = $this->trait->getFreeQuantity($this->identifier);

        $this->assertEquals($quantity, 12);

    }

    public function testAddFreeQuantity()
    {

        $this->trait->setFreeQuantity($this->identifier, 12);
        $this->trait->addFreeQuantity($this->identifier, 11);

        $quantity = $this->trait->getFreeQuantity($this->identifier);

        $this->assertEquals($quantity, 23);

    }

    public function testSubtractFreeQuantity()
    {
        $this->trait->setFreeQuantity($this->identifier, 12);
        $this->trait->subtractFreeQuantity($this->identifier, 3);

        $quantity = $this->trait->getFreeQuantity($this->identifier);

        $this->assertEquals($quantity, 9);

    }

    public function testHasFreeItems()
    {
        $this->assertFalse($this->trait->hasFreeItems());
        $this->assertFalse($this->trait->hasFreeItems($this->identifier));

        $this->trait->setFreeQuantity($this->identifier, 12);

        $this->assertTrue($this->trait->hasFreeItems($this->identifier));
        $this->assertTrue($this->trait->hasFreeItems());

    }

    public function testGetFreeQuantities()
    {
        $quantities = $this->trait->getFreeQuantities();
        $this->assertEquals($quantities, []);

        $this->trait->setFreeQuantity($this->identifier, 12);

        $quantities = $this->trait->getFreeQuantities();

        $this->assertEquals($quantities, ['' => 12]);

    }



}
