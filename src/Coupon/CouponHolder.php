<?php

namespace Heystack\Deals\Coupon;

use Heystack\Core\Identifier\Identifier;
use Heystack\Core\Identifier\IdentifierInterface;
use Heystack\Core\Interfaces\HasDataInterface;
use Heystack\Core\Interfaces\HasEventServiceInterface;
use Heystack\Core\Interfaces\HasStateServiceInterface;
use Heystack\Core\State\State;
use Heystack\Core\State\StateableInterface;
use Heystack\Core\Traits\HasEventServiceTrait;
use Heystack\Core\Traits\HasStateServiceTrait;
use Heystack\Deals\Events;
use Heystack\Deals\Interfaces\CouponHolderInterface;
use Heystack\Deals\Interfaces\CouponInterface;
use Heystack\Ecommerce\Transaction\Traits\TransactionModifierStateTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CouponHolder
    implements
    CouponHolderInterface,
    StateableInterface,
    HasStateServiceInterface,
    HasEventServiceInterface,
    HasDataInterface
{
    use TransactionModifierStateTrait;
    use HasStateServiceTrait;
    use HasEventServiceTrait;

    const IDENTIFIER = 'coupon_holder';

    protected $coupons = [];

    public function __construct(
        State $stateService,
        EventDispatcherInterface $eventService
    )
    {
        $this->setStateService($stateService);
        $this->setEventService($eventService);
    }

    public function getIdentifier()
    {
        return new Identifier(self::IDENTIFIER);
    }

    public function setCoupons(array $coupons)
    {
        $this->coupons = [];

        foreach ($coupons as $coupon) {
            if ($coupon instanceof CouponInterface) {
                $this->coupons[$coupon->getIdentifier()->getFull()] = $coupon;
            } else {
                throw new \InvalidArgumentException(
                    'Coupons provided to setCoupons must be instances of CouponInterface'
                );
            }
        }

        $this->saveState();
        $this->getEventService()->dispatch(Events::COUPON_ADDED);
    }

    public function getCoupon(IdentifierInterface $identifier)
    {
        $identifierText = $identifier->getFull();

        return isset($this->coupons[$identifierText]) ? $this->coupons[$identifierText] : null;
    }

    public function getCoupons(IdentifierInterface $dealIdentifier = null)
    {
        if ($dealIdentifier instanceof IdentifierInterface) {

            $dealCoupons = [];

            foreach ($this->coupons as $coupon) {
                if ($coupon instanceof CouponInterface && $coupon->getDealIdentifier()->isMatch($dealIdentifier)) {
                    $dealCoupons[] = $coupon;
                }
            }

            return $dealCoupons;
        }

        return $this->coupons;

    }

    public function addCoupon(CouponInterface $coupon)
    {
        $this->coupons[$coupon->getIdentifier()->getFull()] = $coupon;

        $this->saveState();
        $this->getEventService()->dispatch(Events::COUPON_ADDED);
    }

    public function removeCoupon(IdentifierInterface $identifier)
    {
        $identifierText = $identifier->getFull();

        if (isset($this->coupons[$identifierText])) {

            unset($this->coupons[$identifierText]);

            $this->saveState();
            $this->getEventService()->dispatch(Events::COUPON_REMOVED);
        }
    }

    public function getData()
    {
        return [$this->coupons];
    }

    public function setData($data)
    {
        if (is_array($data)) {
            list($this->coupons) = $data;
        }
    }
}