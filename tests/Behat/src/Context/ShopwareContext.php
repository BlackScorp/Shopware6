<?php
declare(strict_types=1);

namespace Mollie\Behat\Context;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\TableNode;
use Mollie\Behat\Repository\StaticArrayRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;


class ShopwareContext extends BaseContext
{

    /**
     * @Then iam on page :arg1
     */
    public function iamOnPage($arg1): void
    {
        throw new PendingException();
    }

    /**
     * @Given following data :arg1:
     */
    public function followingData($arg1, TableNode $table): void
    {

        $data = [];
        foreach ($table->getHash() as $row) {
            $key = $row['key'];
            unset($row['key']);
            $data[$key] = $row;
        }
        StaticArrayRepository::add($arg1, $data);

    }

    /**
     * @Given following entities :arg1:
     */
    public function followingEntities($arg1, TableNode $table): void
    {
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get($arg1 . '.repository');

        $upsertData = $this->extractData($table);
        $salesChannelContext = $this->getContext();
        $repository->upsert($upsertData, $salesChannelContext->getContext());
    }

}
