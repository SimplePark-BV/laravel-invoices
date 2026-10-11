<?php

declare(strict_types=1);

namespace SimpleParkBv\Invoices\Enums;

/**
 * How the buyer pays an invoice, which decides the payment text in its footer.
 */
enum PaymentMethod: string
{
    /**
     * The buyer transfers the amount to the bank account of the seller.
     */
    case BankTransfer = 'bank_transfer';

    /**
     * The seller collects the amount from the bank account of the buyer.
     */
    case DirectDebit = 'direct_debit';
}
