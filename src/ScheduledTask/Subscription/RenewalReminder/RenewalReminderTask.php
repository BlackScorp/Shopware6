<?php
declare(strict_types=1);

namespace Kiener\MolliePayments\ScheduledTask\Subscription\RenewalReminder;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class RenewalReminderTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'mollie.subscriptions.renewal_reminder';
    }

    public static function getDefaultInterval(): int
    {
        return 60 * 60; // 1 hour
    }
}
