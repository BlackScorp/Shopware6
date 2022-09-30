<?php

namespace MolliePayments\Fixtures\SalesChannel;


use Basecom\FixturePlugin\Fixture;
use Basecom\FixturePlugin\FixtureBag;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;


class SalesChannelFixture extends Fixture
{

    /**
     * @var EntityRepositoryInterface
     */
    private $repoSalesChannels;

    /**
     * @var EntityRepositoryInterface
     */
    private $repoPaymentMethods;


    /**
     * @param EntityRepositoryInterface $repoSalesChannels
     * @param EntityRepositoryInterface $repoPaymentMethods
     */
    public function __construct(EntityRepositoryInterface $repoSalesChannels, EntityRepositoryInterface $repoPaymentMethods)
    {
        $this->repoSalesChannels = $repoSalesChannels;
        $this->repoPaymentMethods = $repoPaymentMethods;
    }


    /**
     * @return string[]
     */
    public function groups(): array
    {
        return [
            'mollie',
        ];
    }

    /**
     * @param FixtureBag $bag
     * @return void
     */
    public function load(FixtureBag $bag): void
    {
        $ctx = Context::createDefaultContext();

        # first delete all existing configurations
        # of the specific sales channels
        $salesChannelIds = $this->repoSalesChannels->searchIds(new Criteria([]), $ctx)->getIds();

        $this->assignPaymentMethods($salesChannelIds, $ctx);
    }

    /**
     * @param array<mixed> $salesChannelIds
     * @param Context $ctx
     * @return void
     */
    private function assignPaymentMethods(array $salesChannelIds, Context $ctx): void
    {
        $paymentUpdates = [];
        $molliePaymentMethodIdsPrepared = [];

        $molliePaymentMethodIds = $this->repoPaymentMethods->searchIds(new Criteria([]), $ctx)->getIds();


        foreach ($molliePaymentMethodIds as $id) {
            $molliePaymentMethodIdsPrepared[] = [
                'id' => $id,
            ];
        }

        foreach ($salesChannelIds as $id) {

            $paymentUpdates[] = [
                'id' => $id,
                'paymentMethods' => $molliePaymentMethodIdsPrepared,
            ];
        }

        $this->repoSalesChannels->update($paymentUpdates, $ctx);
    }


}
