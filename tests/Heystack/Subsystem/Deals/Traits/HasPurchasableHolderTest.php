<?php
namespace Heystack\Subsystem\Deals\Test;

class HasPurchasableHolderTest extends \PHPUnit_Framework_TestCase
{

    protected $trait;
    protected $purchasableHolder;

    protected function setUp()
    {
        $this->trait = $this->getObjectForTrait('Heystack\Subsystem\Deals\Traits\HasPurchasableHolder');
        $this->purchasableHolder = $this->getMockBuilder('Heystack\Subsystem\Products\ProductHolder\ProductHolder')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testIsInitiallyEmpty()
    {

        $this->assertAttributeEmpty('purchasableHolder', $this->trait);

    }

    public function testSetPurchasableHolder()
    {

        $this->trait->setPurchasableHolder($this->purchasableHolder);

        $this->assertAttributeNotEmpty('purchasableHolder', $this->trait);

        return $this->trait;

    }

    /**
     * @depends testSetPurchasableHolder
     */
    public function testGetPurchasableHolder($trait)
    {

        $this->assertEquals($trait->getPurchasableHolder(), $this->purchasableHolder);

    }
}
