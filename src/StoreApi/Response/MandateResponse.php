<?php

declare(strict_types=1);

namespace PayonePayment\StoreApi\Response;

use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\StoreApiResponse;

class MandateResponse extends StoreApiResponse
{
    /** @var EntitySearchResult */
    protected $object;

    public function __construct(EntitySearchResult $object)
    {
        parent::__construct($object);
    }

    public function getSearchResult(): EntitySearchResult
    {
        return $this->object;
    }
}
