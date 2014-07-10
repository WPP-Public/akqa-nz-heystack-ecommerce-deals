<?php

namespace Heystack\Deals\Coupon\Input;

use Heystack\Core\Identifier\Identifier;
use Heystack\Core\Input\ProcessorInterface;
use Heystack\Deals\Interfaces\CouponHolderInterface;
use Heystack\Deals\Interfaces\CouponInterface;
use Heystack\Deals\Interfaces\HasCouponHolderInterface;
use Heystack\Deals\Traits\HasCouponHolderTrait;

/**
 * @package Heystack\Deals\Coupon\Input
 */
class Processor  implements ProcessorInterface, HasCouponHolderInterface
{
    use HasCouponHolderTrait;

    /**
     * @var string
     */
    protected $couponClass;

    /**
     * Creates the Processor object
     * @param string $couponClass
     * @param CouponHolderInterface $couponHolder
     */
    public function __construct($couponClass, CouponHolderInterface $couponHolder)
    {
        $this->couponClass = $couponClass;
        $this->setCouponHolder($couponHolder);
    }

    /**
     * Returns the identifier of the processor
     * @return \Heystack\Core\Identifier\Identifier
     */
    public function getIdentifier()
    {
        return new Identifier(strtolower($this->couponClass));
    }

    /**
     * Executes the main functionality of the input processor
     * @param  \SS_HTTPRequest $request Request to process
     * @return mixed
     */
    public function process(\SS_HTTPRequest $request)
    {
        $couponHolder = $this->getCouponHolder();

        if ($request->param('ID') == 'add') {
            $couponCode = $request->requestVar('couponcode');

            if (!$couponCode) {
                return [
                    'Success' => false
                ];
            }

            /** @var CouponInterface[] $dbCoupons */
            $dbCoupons = array_filter($this->getCouponsFromDatabase($couponCode), function ($coupon) {
                return $coupon instanceof CouponInterface && $coupon->isValid();
            });
            
            $coupons = $couponHolder->getCoupons();

            if (count($dbCoupons) > 0) {
                foreach ($dbCoupons as $coupon) {
                    $coupons[] = $coupon;
                }

                $couponHolder->setCoupons($coupons);

                return [
                    'Success' => true
                ];
            }

        } elseif ($request->param('ID') == 'remove') {
            /** @var CouponInterface[] $dbCoupons */
            $dbCoupons = array_filter($this->getCouponsFromDatabase($request->param('OtherID')), function ($coupon) {
                return $coupon instanceof CouponInterface;
            });

            $coupons = $couponHolder->getCoupons();
            
            if (count($dbCoupons) > 0) {
                foreach ($dbCoupons as $coupon) {
                    unset($coupons[$coupon->getIdentifier()->getFull()]);
                }
                
                $couponHolder->setCoupons($coupons);

                return [
                    'Success' => true
                ];
            }
        }

        return [
            'Success' => false
        ];
    }

    /**
     * @param $couponCode
     * @return array
     */
    protected function getCouponsFromDatabase($couponCode)
    {
        return \DataList::create($this->couponClass)->filter("Code", $couponCode)->toArray();
    }
}