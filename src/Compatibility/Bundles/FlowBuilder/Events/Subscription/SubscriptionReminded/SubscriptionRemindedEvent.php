<?php
declare(strict_types=1);

namespace Kiener\MolliePayments\Compatibility\Bundles\FlowBuilder\Events\Subscription\SubscriptionReminded;

use Kiener\MolliePayments\Components\Subscription\DAL\Subscription\SubscriptionDefinition;
use Kiener\MolliePayments\Components\Subscription\DAL\Subscription\SubscriptionEntity;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\BusinessEventInterface;
use Shopware\Core\Framework\Event\CustomerAware;
use Shopware\Core\Framework\Event\EventData\EntityType;
use Shopware\Core\Framework\Event\EventData\EventDataCollection;
use Shopware\Core\Framework\Event\EventData\MailRecipientStruct;
use Shopware\Core\Framework\Event\MailAware;
use Shopware\Core\Framework\Event\SalesChannelAware;
use Shopware\Core\Framework\Struct\JsonSerializableTrait;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Symfony\Contracts\EventDispatcher\Event;

class SubscriptionRemindedEvent extends Event implements CustomerAware, SalesChannelAware, MailAware, BusinessEventInterface
{
    use JsonSerializableTrait;

    /**
     * @var SubscriptionEntity
     */
    protected $subscription;

    /**
     * @var CustomerEntity
     */
    protected $customer;

    /**
     * @var SalesChannelEntity
     */
    protected $salesChannel;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var MailRecipientStruct
     */
    protected $mailRecipient;

    public function __construct(CustomerEntity $customer, SubscriptionEntity $subscription, SalesChannelEntity $salesChannel, Context $context)
    {
        $this->subscription = $subscription;
        $this->customer = $customer;
        $this->salesChannel = $salesChannel;
        $this->context = $context;

        $this->mailRecipient = new MailRecipientStruct(
            [
                $customer->getEmail() => $customer->getFirstName() . ' ' . $customer->getLastName(),
            ]
        );
    }

    public function getName(): string
    {
        return 'mollie.subscription.renewal_reminder';
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getSalesChannelId(): string
    {
        return $this->subscription->getSalesChannelId();
    }

    public function getCustomerId(): string
    {
        return $this->subscription->getCustomerId();
    }

    public function getCustomer(): CustomerEntity
    {
        return $this->customer;
    }

    public function getSalesChannel(): SalesChannelEntity
    {
        return $this->salesChannel;
    }

    public function getSubscription(): SubscriptionEntity
    {
        return $this->subscription;
    }

    public function getMailStruct(): MailRecipientStruct
    {
        return $this->mailRecipient;
    }

    public static function getAvailableData(): EventDataCollection
    {
        $data = new EventDataCollection();

        $data->add('customer', new EntityType(CustomerDefinition::class));
        $data->add('subscription', new EntityType(SubscriptionDefinition::class));
        $data->add('salesChannel', new EntityType(SalesChannelDefinition::class));

        return $data;
    }
}
