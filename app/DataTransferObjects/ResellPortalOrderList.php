<?php

namespace App\DataTransferObjects;

readonly class ResellPortalOrderList
{
    /**
     * @param  list<ResellPortalOrder>  $rows
     */
    public function __construct(
        public bool $available,
        public array $rows,
    ) {}
}
