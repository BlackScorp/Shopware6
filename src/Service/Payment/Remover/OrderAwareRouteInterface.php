<?php
declare(strict_types=1);

namespace Kiener\MolliePayments\Service\Payment\Remover;

interface OrderAwareRouteInterface
{
    public const ORDER_ROUTES = [
        'frontend.account.edit-order.page',
    ];

    public function isOrderRoute(): bool;

    public function getOrder(\Shopware\Core\Framework\Context $context): \Shopware\Core\Checkout\Order\OrderEntity;
}
