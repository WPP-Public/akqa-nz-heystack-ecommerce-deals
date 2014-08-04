<?php

namespace Heystack\Deals\Coupon\Output;

use Heystack\Core\Identifier\Identifier;
use Heystack\Core\Output\ProcessorInterface;

class Processor implements ProcessorInterface
{
    protected $couponClass;

    /**
     * @param string $couponClass
     */
    public function __construct($couponClass)
    {
        $this->couponClass = $couponClass;
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
     * Executes the main functionality of the output processor
     *
     * @param \Controller $controller The relevant SilverStripe controller
     * @param mixed $result The result from the input processor
     * @return mixed|null
     */
    public function process(\Controller $controller, $result = null)
    {
        if ($controller->getRequest()->isAjax()) {

            $response = $controller->getResponse();
            $response->setStatusCode(200);
            $response->addHeader('Content-Type', 'application/json');

            $response->setBody(json_encode($result));

            return $response;
        } else {
            $controller->redirectBack();
        }

        return null;
    }


} 