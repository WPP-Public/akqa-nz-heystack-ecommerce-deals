<?php

namespace Heystack\Deals\Interfaces;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @author Cam Spiers <camspiers@gmail.com>
 * @package Ecommerce-Deals
 */
interface ResultInterface extends EventSubscriberInterface
{
    /**
     * Returns a short string that describes what the result does
     * @return string
     */
    public function getDescription();

    /**
     * Main function that determines what the result does
     * @param DealHandlerInterface $dealHandler
     * @return \SebastianBergmann\Money\Money
     */
    public function process(DealHandlerInterface $dealHandler);

    /**
     * Indicates the type of amount the modifier will return
     * Must return a constant from TransactionModifierTypes
     * @return string
     */
    public function getType();

    /**
     * @return \Heystack\Ecommerce\Transaction\Interfaces\TransactionModifierInterface[]
     */
    public function getLinkedModifiers();
}
