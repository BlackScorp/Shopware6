<?php
declare(strict_types=1);

namespace Kiener\MolliePayments\Service\Payment\Remover;

use Kiener\MolliePayments\Service\MollieApi\OrderItemsExtractor;
use Kiener\MolliePayments\Service\OrderService;
use Kiener\MolliePayments\Service\SettingsService;
use Kiener\MolliePayments\Struct\PaymentMethod\PaymentMethodAttributes;
use Mollie\Api\Types\PaymentMethod;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Payment\SalesChannel\PaymentMethodRouteResponse;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class RegularPaymentRemover extends PaymentMethodRemover
{
    public function __construct(ContainerInterface $container, RequestStack $requestStack, OrderService $orderService, SettingsService $settingsService, OrderItemsExtractor $orderDataExtractor, LoggerInterface $logger)
    {
        parent::__construct($container, $requestStack, $orderService, $settingsService, $orderDataExtractor, $logger);
    }

    public function removePaymentMethods(PaymentMethodRouteResponse $originalData, SalesChannelContext $context): PaymentMethodRouteResponse
    {
        if (! $this->isAllowedRoute()) {
            return $originalData;
        }

        foreach ($originalData->getPaymentMethods() as $key => $paymentMethod) {
            $attributes = new PaymentMethodAttributes($paymentMethod);

            // SEPA Direct Debit is only allowed when the customer updates a running subscription.
            // this means, in our plugin, it's not allowed anymore
            if ($attributes->getMollieIdentifier() === PaymentMethod::DIRECTDEBIT) {
                $originalData->getPaymentMethods()->remove($key);
            }

            // ING HomePay is deprecated
            if ($attributes->getMollieIdentifier() === PaymentMethod::INGHOMEPAY) {
                $originalData->getPaymentMethods()->remove($key);
            }

            // hiding billie for none business customers
            if ($attributes->getMollieIdentifier() === PaymentMethod::BILLIE && $this->companyNameExists($context) === false) {
                $originalData->getPaymentMethods()->remove($key);
            }
        }

        return $originalData;
    }

    private function companyNameExists(SalesChannelContext $context): bool
    {
        $customer = $context->getCustomer();

        if ($customer === null) {
            return false;
        }

        $billingAddress = $customer->getActiveBillingAddress();

        if ($billingAddress === null) {
            $billingAddress = $customer->getDefaultBillingAddress();
        }

        return $billingAddress && ! empty($billingAddress->getCompany());
    }
}
