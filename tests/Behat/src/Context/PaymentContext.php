<?php
declare(strict_types=1);

namespace Mollie\Behat\Context;

use Behat\Behat\Tester\Exception\PendingException;

final class PaymentContext extends BaseContext
{
    /**
     * @Then select status :arg1
     */
    public function selectStatus($arg1): void
    {
        throw new PendingException();
    }
}