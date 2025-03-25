<?php
declare(strict_types=1);

namespace Mollie\Behat\Context;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;

use Mollie\Behat\Repository\StaticArrayRepository;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartItemAddRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\SalesChannel\UpsertAddressRoute;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\PlatformRequest;
use Shopware\Core\SalesChannelRequest;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\CartLineItemController;
use Shopware\Storefront\Controller\CheckoutController;
use Shopware\Storefront\Framework\Routing\RequestTransformer;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertTrue;
use Phpunit\Framework\Assert;

final class CartContext extends BaseContext
{

    private ?Cart $cart = null;


    /**
     * @Given i set billing country to :arg1
     */
    public function iSetBillingCountryTo($arg1): void
    {
        $salesChannelContext = $this->getSalesChannelContext();
        $loggedInCustomer = $salesChannelContext->getCustomer();

        $country = $this->getCountryByIso($arg1);
        $upsertAddressRoute = $this->getContainer()->get(UpsertAddressRoute::class);
        $customerAddress = $loggedInCustomer->getDefaultBillingAddress();
        $customerAddress->setCountryId($country->getId());
        $data = new RequestDataBag([
            'address' => $customerAddress->getVars()
        ]);


        $address = $data->get('address');

        $upsertAddressRoute->upsert(
            $address->get('id'),
            $address->toRequestDataBag(),
            $salesChannelContext,
            $salesChannelContext->getCustomer()
        );


    }

    /**
     * @Given i add :arg1 products :arg2 to cart
     */
    public function iAddProductsToCart($arg1, $arg2): void
    {
        $salesChannelContext = $this->getSalesChannelContext();
        $request = $this->createRequest('frontend.checkout.line-item.add', $salesChannelContext);

        $cartService = $this->getContainer()->get(CartService::class);

        $cart = $cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);

        $cartLineItemController = $this->getContainer()->get(CartLineItemController::class);


        $productId = $this->getProductIdByNumber($arg2);


        $requestData = new RequestDataBag([
            'lineItems'=>[
                $productId => [
                    'id' => $productId,
                    'quantity'=>$arg1,
                    'type'=>'product'
                ]
            ]
        ]);
        /** @var RedirectResponse $response */
        $response = $cartLineItemController->addLineItems($cart, $requestData, $request, $salesChannelContext);

        //dump($response->getTargetUrl());

    }

    /**
     * @When i start checkout with payment :arg1
     */
    public function iStartCheckoutWithPayment($arg1): void
    {


        /** @var EntityRepository $paymentMethodRepository */
        $paymentMethodRepository = $this->getContainer()->get('payment_method.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $arg1));
        $criteria->setLimit(1);

        $paymentMethodSearchResult = $paymentMethodRepository->searchIds($criteria, Context::createDefaultContext());
        $paymentMethodId = $paymentMethodSearchResult->getIds()[0];


        $salesChannelContext = $this->getSalesChannelContext([
            SalesChannelContextService::PAYMENT_METHOD_ID => $paymentMethodId
        ]);

        $checkoutController = $this->getContainer()->get(CheckoutController::class);
        $data = new RequestDataBag([
            'tos' => true
        ]);
        $request = $this->createRequest('frontend.checkout.finish.order', $salesChannelContext);

        /** @var RedirectResponse $response */
        $response = $checkoutController->order($data, $salesChannelContext, $request);
        //$orderId = $this->order($this->cart, $salesChannelContext);
        dump($response);

    }
}