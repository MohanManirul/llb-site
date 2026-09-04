<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing:antialiased;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f3f4f6;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" border="0" style="width:100%; max-width:560px; background-color:#ffffff; border-radius:16px; overflow:hidden;">
                    <tr>
                        <td style="padding:32px 32px 24px 32px; border-bottom:1px solid #e5e7eb;">
                            <h1 style="margin:0 0 8px 0; font-size:22px; line-height:1.3; font-weight:700; color:#111827;">
                                Invoice #{{ $invoice->invoice_number }}
                            </h1>
                            <p style="margin:0; font-size:14px; line-height:1.5; color:#6b7280;">
                                A new invoice has been generated for your project.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px 32px 0 32px;">
                            <p style="margin:0; font-size:15px; line-height:1.7; color:#374151;">
                                Hello,<br>
                                Please review the invoice details below. The remaining balance is due by
                                <strong style="color:#111827; font-weight:700;">{{ $invoice->due_date->format('d M Y') }}.</strong><br>
                                A PDF copy of this invoice is attached to this email.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 32px 0 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
                                <tr>
                                    <td style="padding:14px 20px; font-size:14px; color:#374151; border-bottom:1px solid #e5e7eb;">Project</td>
                                    <td align="right" style="padding:14px 20px; font-size:14px; color:#111827; font-weight:600; border-bottom:1px solid #e5e7eb;">{{ $project->project_name }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 20px; font-size:14px; color:#374151; border-bottom:1px solid #e5e7eb;">Invoice Date</td>
                                    <td align="right" style="padding:14px 20px; font-size:14px; color:#111827; font-weight:600; border-bottom:1px solid #e5e7eb;">{{ $invoice->invoice_date->format('d M Y') }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 20px; font-size:14px; color:#374151; border-bottom:1px solid #e5e7eb;">Total Amount</td>
                                    <td align="right" style="padding:14px 20px; font-size:14px; color:#111827; font-weight:600; border-bottom:1px solid #e5e7eb;">&#2547;{{ number_format($invoice->total_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 20px; font-size:14px; color:#374151; border-bottom:1px solid #e5e7eb;">Paid Amount</td>
                                    <td align="right" style="padding:14px 20px; font-size:14px; color:#111827; font-weight:600; border-bottom:1px solid #e5e7eb;">&#2547;{{ number_format($invoice->paid_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 20px; font-size:14px; color:#374151; border-bottom:1px solid #e5e7eb;">Due Amount</td>
                                    <td align="right" style="padding:14px 20px; font-size:14px; color:#111827; font-weight:600; border-bottom:1px solid #e5e7eb;">&#2547;{{ number_format($invoice->due_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 20px; font-size:14px; color:#374151; background-color:#f9fafb;">Due Date</td>
                                    <td align="right" style="padding:14px 20px; font-size:14px; color:#111827; font-weight:600; background-color:#f9fafb;">{{ $invoice->due_date->format('d M Y') }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:32px 32px 0 32px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" style="background-color:#7c5cfc; border-radius:8px;">
                                        <a href="{{ $url }}" style="display:inline-block; padding:14px 28px; font-size:15px; font-weight:600; color:#ffffff; text-decoration:none;">View Full Details</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:24px 32px 36px 32px;">
                            <p style="margin:0 0 20px 0; font-size:14px; line-height:1.6; color:#9ca3af;">
                                If you have any questions regarding this invoice<br>
                                please feel free to contact us.
                            </p>
                            <p style="margin:0; font-size:15px; color:#6b7280;">
                                <strong style="color:#111827; font-weight:700;">Thank you,</strong> {{ config('app.name') }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
