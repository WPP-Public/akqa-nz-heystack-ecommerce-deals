<?php

namespace Heystack\Deals\Coupon\Input;


use Heystack\Core\Identifier\Identifier;
use Heystack\Core\Input\ProcessorInterface;
use Heystack\Deals\Interfaces\CouponHolderInterface;
use Heystack\Deals\Interfaces\CouponInterface;
use Heystack\Deals\Interfaces\HasCouponHolderInterface;
use Heystack\Deals\Traits\HasCouponHolderTrait;

class Processor  implements ProcessorInterface, HasCouponHolderInterface
{
    use HasCouponHolderTrait;

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
        if ($request->param('ID') == 'add') {

            $couponCode = $request->requestVar('couponcode');

            if (!$couponCode) {
                return [
                    'Success' => false
                ];
            }

            $coupon = $this->getCouponFromDatabase($couponCode);

            if ($coupon instanceof CouponInterface && $coupon->isValid()) {

                $this->getCouponHolder()->addCoupon($coupon);

                return [
                    'Success' => true
                ];
            }

        } elseif ($request->param('ID') == 'remove') {

            $coupon = $this->getCouponFromDatabase($request->param('OtherID'));

            if ($coupon instanceof CouponInterface) {

                $this->voucherService->removeVoucher($coupon->getIdentifier());

                return [
                    'Success' => true
                ];
            }
        }

        return [
            'Success' => false
        ];
    }

    protected function getCouponFromDatabase($couponCode)
    {
        return \DataList::create($this->couponClass)->filter("Code", $couponCode)->first();
    }
}