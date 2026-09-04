<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 32px 36px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #374151; margin: 0; }
        .header { border-bottom: 2px solid #7c5cfc; padding-bottom: 14px; margin-bottom: 22px; }
        .brand { font-size: 20px; font-weight: bold; color: #111827; }
        .doc-title { font-size: 15px; font-weight: bold; color: #7c5cfc; text-align: right; }
        .doc-meta { font-size: 11px; color: #6b7280; text-align: right; }
        .party { width: 100%; margin-bottom: 22px; }
        .party td { vertical-align: top; width: 50%; padding: 0; }
        .label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; padding-bottom: 4px; }
        .value { font-size: 12px; color: #111827; line-height: 1.6; }
        .value strong { color: #111827; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        table.items th { background-color: #f3f4f6; color: #374151; font-size: 11px; text-align: left; padding: 9px 12px; border-bottom: 1px solid #e5e7eb; }
        table.items td { padding: 9px 12px; border-bottom: 1px solid #e5e7eb; font-size: 12px; }
        .right { text-align: right; }
        table.totals { width: 45%; border-collapse: collapse; float: right; }
        table.totals td { padding: 7px 12px; font-size: 12px; border-bottom: 1px solid #e5e7eb; }
        table.totals td.right { text-align: right; font-weight: bold; color: #111827; }
        table.totals tr.grand td { background-color: #f9fafb; font-size: 13px; border-bottom: none; }
        .status { display: inline-block; padding: 4px 10px; border-radius: 10px; font-size: 10px; font-weight: bold; }
        .status-paid { background-color: #dcfce7; color: #166534; }
        .status-due { background-color: #fee2e2; color: #991b1b; }
        .footer { position: fixed; bottom: -12px; left: 0; right: 0; text-align: center; font-size: 10px; color: #9ca3af; }
    </style>
</head>
<body>
    <table class="header" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <div class="brand">{{ config('app.name') }}</div>
            </td>
            <td>
                <div class="doc-title">INVOICE</div>
                <div class="doc-meta">
                    {{ $invoice->invoice_number }}<br>
                    Issued: {{ $invoice->invoice_date->format('d M Y') }}<br>
                    Due: {{ $invoice->due_date->format('d M Y') }}
                </div>
            </td>
        </tr>
    </table>

    <table class="party" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <div class="label">Billed To</div>
                <div class="value">
                    <strong>{{ $client?->name ?? $project->contact_person ?? 'Client' }}</strong><br>
                    @if ($client?->email ?? $project->contact_email)
                        {{ $client?->email ?? $project->contact_email }}<br>
                    @endif
                    @if ($client?->phone ?? $project->contact_phone)
                        {{ $client?->phone ?? $project->contact_phone }}<br>
                    @endif
                    @if ($client?->address)
                        {{ $client->address }}
                    @endif
                </div>
            </td>
            <td>
                <div class="label">Payment Status</div>
                <div class="value">
                    @if ((float) $invoice->due_amount <= 0)
                        <span class="status status-paid">FULLY PAID</span>
                    @else
                        <span class="status status-due">DUE BDT {{ number_format($invoice->due_amount, 2) }}</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Description</th>
                <th class="right">Amount (BDT)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>{{ $project->project_name }}</strong>
                    @if ($project->business_name)
                        <br>{{ $project->business_name }}
                    @endif
                    @if ($project->start_date && $project->end_date)
                        <br>{{ $project->start_date->format('d M Y') }} &ndash; {{ $project->end_date->format('d M Y') }}
                    @endif
                </td>
                <td class="right">{{ number_format($invoice->total_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>Total Amount</td>
            <td class="right">{{ number_format($invoice->total_amount, 2) }}</td>
        </tr>
        <tr>
            <td>Paid Amount</td>
            <td class="right">{{ number_format($invoice->paid_amount, 2) }}</td>
        </tr>
        <tr class="grand">
            <td>Due Amount</td>
            <td class="right">{{ number_format($invoice->due_amount, 2) }}</td>
        </tr>
    </table>

    <div class="footer">
        {{ config('app.name') }} &middot; This is a computer generated invoice and needs no signature.
    </div>
</body>
</html>
