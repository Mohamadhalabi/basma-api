<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when an order line requests more stock than is available
 * and the product does not allow backorders. Causes the whole order
 * transaction to roll back.
 */
class InsufficientStockException extends Exception
{
    public function __construct(
        public string $sku,
        public int $requested,
        public int $available,
    ) {
        parent::__construct(
            "Insufficient stock for SKU {$sku}: requested {$requested}, available {$available}."
        );
    }
}
