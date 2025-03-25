<?php
declare(strict_types=1);

namespace Mollie\Behat\Context;


use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\TableNode;
use Mollie\Behat\Repository\StaticArrayRepository;
use PHPUnit\Framework\Assert;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountService;
use Shopware\Core\Checkout\Customer\SalesChannel\LoginRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\RegisterRoute;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Storefront\Controller\AuthController;


final class AccountContext extends BaseContext
{

    public const CUSTOMER = 'customerId';
    public const TOKEN = 'token';

    /**
     * @Given i register with following data:
     */
    public function iRegisterWithFollowingData(TableNode $table): void
    {
        $salesChannelContext = $this->getContext();
        $registerRoute = $this->getContainer()->get(RegisterRoute::class);
        $data = $this->extractData($table);
        $data = array_pop($data);

        $data['storefrontUrl'] = 'https://mollie-local.diwc.de';
        $data = new RequestDataBag($data);

        try {
            $registerRoute->register($data, $salesChannelContext);
        } catch (ConstraintViolationException $e) {
            // Skip email already in use error
            $emailUsedViolation = $e->getViolations('/email');
            if ($emailUsedViolation->count() === 1) {
                return;
            }

            throw $e;
        }


    }

    /**
     * @Given iam logged in as with username :arg1 and password :arg2
     */
    public function iamLoggedInAsWithUsernameAndPassword($arg1, $arg2): void
    {
        $salesChannelContext = $this->getContext();

        $accountService = $this->getContainer()->get(AccountService::class);
        $customer = $accountService->getCustomerByLogin($arg1, $arg2, $salesChannelContext);


        $authController = $this->getContainer()->get(AuthController::class);
        $request = $this->createRequest('frontend.account.login', $salesChannelContext);
        $request->request->set('redirectTo', 'frontend.account.home.page');
        $data = new RequestDataBag([
            'username' => $arg1,
            'password' => $arg2,
            'redirectTo' => 'frontend.account.home.page'
        ]);

        $response = $authController->login($request, $data, $salesChannelContext);
        $contextToken = $request->getSession()->getBag('attributes')->get('sw-context-token');
        StaticArrayRepository::add(AccountContext::TOKEN, $contextToken);
        StaticArrayRepository::add(AccountContext::CUSTOMER, $customer->getId());
        Assert::assertNotEmpty($contextToken);
        Assert::assertSame(302, $response->getStatusCode());

    }


}