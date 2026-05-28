<?php

namespace App\Events;

use App\Models\OrderInvoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderInvoiceGenerated
{
    use Dispatchable, SerializesModels;

    public function __construct(public OrderInvoice $invoice)
    {
    }
}
