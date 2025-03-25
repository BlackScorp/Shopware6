<?php
declare(strict_types=1);

namespace Mollie\Behat\Context;

use Behat\Behat\Context\Context as BehatContext;
use Behat\Gherkin\Node\TableNode;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use Mollie\Behat\Repository\StaticArrayRepository;
use PHPUnit\Framework\Assert;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\SessionTestBehaviour;
use Shopware\Core\Framework\Util\Random;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\PlatformRequest;
use Shopware\Core\SalesChannelRequest;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\Integration\Traits\TestShortHands;
use Shopware\Core\Test\TestDefaults;
use Shopware\Storefront\Framework\Routing\RequestTransformer;
use Symfony\Component\HttpFoundation\Request;

abstract class BaseContext extends Assert implements BehatContext
{
    use IntegrationTestBehaviour;
    use TestShortHands;
    use SessionTestBehaviour;

    private array $salesChannelContextMap = [];


    /**
     * @BeforeSuite
     *
     * @return void
     * @throws \JsonException
     *
     */
    public static function prepare(BeforeSuiteScope $scope)
    {
        // TODO: move to behat.yaml somehow
        require_once __DIR__ . '/../../../bootstrap/index.php';
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function getContext(?string $token = null, array $options = []): SalesChannelContext
    {
        $token = StaticArrayRepository::get('token',Uuid::randomHex());
        $domain = 'https://mollie-local.diwc.de';

        /** @var EntityRepository $salesChannelRepository */
        $salesChannelRepository = $this->getContainer()->get('sales_channel.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addFilter(new EqualsFilter('domains.url', $domain));
        $salesChannelIds = $salesChannelRepository->searchIds($criteria, Context::createDefaultContext())->getIds();
        $salesChannelId = $salesChannelIds[0];

        return $this->getContainer()->get(SalesChannelContextFactory::class)
            ->create($token, $salesChannelId, $options);
    }

    protected function getSalesChannelContext(array $options = []): SalesChannelContext
    {

        $customerId = StaticArrayRepository::get(AccountContext::CUSTOMER);
        $options[SalesChannelContextService::CUSTOMER_ID] = $customerId;
        return $this->getContext(null, $options);
    }

    protected function createRequest(string $route, SalesChannelContext $salesChannelContext, array $params = []): Request
    {

        $request = new Request();
        $request->query->add($params);
        $request->setSession($this->getSession());
        $request->attributes->add([
            '_route' => $route,
            SalesChannelRequest::ATTRIBUTE_IS_SALES_CHANNEL_REQUEST => true,
            PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID => $salesChannelContext->getSalesChannelId(),
            PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT => $salesChannelContext,
            RequestTransformer::STOREFRONT_URL => 'https://mollie-local.diwc.de',
        ]);
        $this->getContainer()->get('request_stack')->push($request);
        return $request;
    }

    protected function getProductIdByNumber(string $productNumber): string
    {
        $productRepository = $this->getContainer()->get('product.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productNumber', $productNumber));


        $searchResult = $productRepository->searchIds($criteria, Context::createDefaultContext());
        $productId = $searchResult->getIds()[0] ?? null;
        if ($productId === null) {
            throw new \Exception(sprintf('Product "%s" not found', $productNumber));
        }
        return $productId;
    }

    protected function getCountryByIso(string $countryIso): CountryEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', $countryIso));

        $countryRepository = $this->getContainer()->get('country.repository');
        return $countryRepository->search($criteria, Context::createDefaultContext())->first();
    }


    protected function extractData(TableNode $table): array
    {
        $data = [];
        foreach ($table->getIterator() as $row) {
            $entry = [];
            foreach ($row as $field => $value) {
                if (stripos($field, 'id') !== false && ! Uuid::isValid($value)) {
                    $value = Uuid::fromBytesToHex(md5($value, true)); //fromStrToHex does not exists in 6.4
                }
                if (stripos($value, '$') === 0) {
                    $staticData = StaticArrayRepository::get($field);
                    if ($staticData !== null) {
                        $key = str_replace('$', '', $value);
                        $value = $staticData[$key] ?? null;
                    }
                }
                $entry[$field] = $value;
            }

            $data[] = $entry;
        }
        return $data;
    }
}