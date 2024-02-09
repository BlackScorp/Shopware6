<?php

namespace Kiener\MolliePayments\Handler\Method;

use Kiener\MolliePayments\Handler\PaymentHandler;
use Kiener\MolliePayments\Service\CustomFieldService;
use Mollie\Api\Types\PaymentMethod;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\Locale\LocaleEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PayPalExpressPayment extends PaymentHandler
{

    /**
     *
     */
    public const PAYMENT_METHOD_NAME = 'paypalexpress';

    /**
     *
     */
    public const PAYMENT_METHOD_DESCRIPTION = 'PayPal Express';

    /** @var string */
    protected $paymentMethod = PaymentMethod::PAYPAL;


    /**
     * @param array<mixed> $orderData
     * @param OrderEntity $orderEntity
     * @param SalesChannelContext $salesChannelContext
     * @param CustomerEntity $customer
     * @return array<mixed>
     */
    public function processPaymentMethodSpecificParameters(array $orderData, OrderEntity $orderEntity, SalesChannelContext $salesChannelContext, CustomerEntity $customer): array
    {
        $orderData['authenticationId'] = $customer->getCustomFields()[CustomFieldService::CUSTOM_FIELDS_KEY_MOLLIE_PAYMENTS]['ppe_auth_id'];
        return $orderData;
    }

}