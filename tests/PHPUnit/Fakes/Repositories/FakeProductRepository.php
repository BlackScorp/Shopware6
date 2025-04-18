<?php
declare(strict_types=1);

namespace MolliePayments\Tests\Fakes\Repositories;

use Shopware\Core\Content\Product\Exception\ProductNotFoundException;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class FakeProductRepository extends EntityRepository
{
    public bool $throwExceptions = false;
    /**
     * @var ?ProductEntity
     */
    private $searchResultID;

    /**
     * @var ?ProductEntity
     */
    private $searchResultNumber;

    public function __construct(?ProductEntity $resultID, ?ProductEntity $resultNumber)
    {
        $this->searchResultID = $resultID;
        $this->searchResultNumber = $resultNumber;
    }

    /**
     * @return array<ProductEntity>
     */
    public function findByID(string $productId, SalesChannelContext $context): array
    {
        if ($this->searchResultID === null) {
            return [];
        }

        return [$this->searchResultID];
    }

    /**
     * @return array<ProductEntity>
     */
    public function findByNumber(string $productNumber, SalesChannelContext $context): array
    {
        if ($this->searchResultNumber === null) {
            return [];
        }

        return [$this->searchResultNumber];
    }

    public function search(Criteria $criteria, Context $context): EntitySearchResult
    {
        if ($this->throwExceptions) {
            throw new ProductNotFoundException('test');
        }

        $entities = new EntityCollection();

        if ($this->searchResultNumber !== null) {
            $entities->add($this->searchResultNumber);
        }

        if ($this->searchResultID !== null) {
            $entities->add($this->searchResultID);
        }

        return new EntitySearchResult(ProductEntity::class, $entities->count(), $entities, null, $criteria, $context);
    }
}
