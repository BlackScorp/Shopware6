<?php
declare(strict_types=1);

namespace Kiener\MolliePayments\Controller\Storefront\POS;

use Kiener\MolliePayments\Controller\Storefront\AbstractStoreFrontController;
use Kiener\MolliePayments\Gateway\MollieGatewayInterface;
use Kiener\MolliePayments\Repository\OrderTransactionRepository;
use Kiener\MolliePayments\Service\CustomerService;
use Kiener\MolliePayments\Service\Mollie\MolliePaymentStatus;
use Kiener\MolliePayments\Service\Mollie\OrderStatusConverter;
use Kiener\MolliePayments\Service\Order\OrderStatusUpdater;
use Kiener\MolliePayments\Traits\Storefront\RedirectTrait;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class PosControllerBase extends AbstractStoreFrontController
{
    use RedirectTrait;

    /**
     * @var CustomerService
     */
    private $customerService;

    /**
     * @var MollieGatewayInterface
     */
    private $mollieGateway;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var OrderStatusUpdater
     */
    private $statusUpdater;

    /**
     * @var OrderStatusConverter
     */
    private $statusConverter;

    /**
     * @var OrderTransactionRepository
     */
    private $repoTransactions;

    public function __construct(CustomerService $customerService, MollieGatewayInterface $mollieGateway, RouterInterface $router, OrderStatusUpdater $orderStatusUpdater, OrderStatusConverter $statusConverter, OrderTransactionRepository $repoTransactions)
    {
        $this->customerService = $customerService;
        $this->mollieGateway = $mollieGateway;
        $this->router = $router;
        $this->statusUpdater = $orderStatusUpdater;
        $this->statusConverter = $statusConverter;
        $this->repoTransactions = $repoTransactions;
    }

    public function storeTerminal(SalesChannelContext $context, string $customerId, string $terminalId): JsonResponse
    {
        $result = null;

        $customer = $this->customerService->getCustomer($customerId, $context->getContext());

        if ($customer instanceof CustomerEntity) {
            $writtenEvent = $this->customerService->setPosTerminal(
                $customer,
                $terminalId,
                $context->getContext()
            );

            $result = $writtenEvent->getErrors();
        }

        return new JsonResponse([
            'success' => (bool) $result,
            'customerId' => $customerId,
            'result' => $result,
        ]);
    }

    public function checkoutAction(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $orderId = $request->get('sw');
        $mollieId = $request->get('mo');
        $changeStatusUrl = (string) $request->get('cs');

        $params = [
            'swOrderId' => $orderId,
            'molliePaymentId' => $mollieId,
        ];

        if (! empty($changeStatusUrl)) {
            $params['changeStatusUrl'] = $changeStatusUrl;
        }

        return $this->renderStorefront('@Storefront/mollie/pos/checkout.html.twig', $params);
    }

    public function statusAction(SalesChannelContext $context, string $orderId, string $molliePaymentId): JsonResponse
    {
        $this->mollieGateway->switchClient($context->getSalesChannelId());

        $molliePayment = $this->mollieGateway->getPayment($molliePaymentId);

        $ready = false;

        $status = $this->statusConverter->getMolliePaymentStatus($molliePayment);

        if ($status !== MolliePaymentStatus::MOLLIE_PAYMENT_OPEN) {
            $ready = true;
        }

        $url = '';

        if ($ready) {
            if (MolliePaymentStatus::isApprovedStatus($molliePayment->status)) {
                $url = $this->getCheckoutFinishPage($orderId, $this->router);
            } else {
                $url = $this->getEditOrderPage($orderId, $this->router);
            }

            $latestTransaction = $this->repoTransactions->getLatestOrderTransaction($orderId, $context->getContext());

            $this->statusUpdater->updatePaymentStatus(
                $latestTransaction,
                $status,
                $context->getContext()
            );
        }

        $isSuccess = MolliePaymentStatus::isApprovedStatus($molliePayment->status);

        return new JsonResponse([
            'ready' => $ready,
            'redirectUrl' => $url,
            'success' => $isSuccess,
        ]);
    }
}
