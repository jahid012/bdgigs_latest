<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $receipt['invoiceId'] ?? $receipt['order_id'] ?? '' }}</title>
    <style>
        body { background: #f8fafc; color: #0f172a; font-family: Inter, Arial, sans-serif; margin: 0; padding: 32px; }
        main { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; margin: 0 auto; max-width: 760px; padding: 32px; }
        h1 { margin: 0 0 8px; }
        p { color: #64748b; margin: 0 0 24px; }
        dl { display: grid; gap: 12px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        div { border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; }
        dt { color: #64748b; font-size: 12px; font-weight: 800; text-transform: uppercase; }
        dd { font-size: 16px; font-weight: 700; margin: 4px 0 0; }
        @media print { body { background: #fff; padding: 0; } main { border: 0; } }
    </style>
</head>
<body>
    <main>
        <h1>{{ $receipt['platform_name'] ?? config('app.name') }} Receipt</h1>
        <p>{{ $receipt['invoiceId'] ?? 'Receipt' }} issued {{ $receipt['issuedAt'] ?? $receipt['date'] ?? '' }}</p>
        <dl>
            @foreach ([
                'Order ID' => 'order_id',
                'Buyer' => 'buyer_name',
                'Seller' => 'seller_name',
                'Service' => 'gig_title',
                'Amount' => 'amount',
                'Platform fee' => 'platform_fee',
                'Seller earning' => 'seller_earning',
                'Payment method' => 'payment_method',
                'Transaction ID' => 'transaction_id',
                'Date' => 'date',
            ] as $label => $key)
                <div>
                    <dt>{{ $label }}</dt>
                    <dd>{{ $receipt[$key] ?? 'N/A' }}</dd>
                </div>
            @endforeach
        </dl>
    </main>
</body>
</html>
