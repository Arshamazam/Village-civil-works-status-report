<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>PRSWSSP Daily Site Mobilization Report</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 20px; color: #1e293b; line-height: 1.5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 640px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0;">
        <!-- Header -->
        <tr>
            <td style="background-color: #0f172a; padding: 24px; color: #ffffff;">
                <div style="font-size: 11px; font-weight: 700; color: #10b981; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">
                    PRMSC &bull; Punjab Rural Municipal Services Company
                </div>
                <h1 style="font-size: 18px; font-weight: 800; margin: 0; color: #ffffff;">
                    Daily Site Mobilization Report
                </h1>
                <div style="font-size: 12px; color: #94a3b8; margin-top: 4px;">
                    {{ \Carbon\Carbon::parse($payload['report_date'] ?? $date)->format('l, d F Y') }}
                    @if(!empty($tehsilName))
                    &bull; <strong style="color: #ffffff;">Tehsil {{ $tehsilName }}</strong>
                    @endif
                </div>
            </td>
        </tr>

        <!-- Content Body -->
        <tr>
            <td style="padding: 24px;">
                <p style="font-size: 14px; margin-top: 0; margin-bottom: 16px;">
                    Dear Project Leadership & Engineers,<br>
                    Here is the daily executive mobilization snapshot for civil works sites under PRSWSSP. The comprehensive 3-page mobilization report PDF is attached to this email.
                </p>

                <!-- KPI Strip (6 Cards Table) -->
                <table width="100%" cellpadding="0" cellspacing="6" style="margin-bottom: 20px;">
                    <tr>
                        <td width="33%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; text-align: center;">
                            <div style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase;">Ongoing</div>
                            <div style="font-size: 20px; font-weight: 800; color: #0f172a;">{{ $payload['total_ongoing'] ?? 133 }}</div>
                        </td>
                        <td width="33%" style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 10px; text-align: center;">
                            <div style="font-size: 10px; font-weight: 700; color: #166534; text-transform: uppercase;">Active</div>
                            <div style="font-size: 20px; font-weight: 800; color: #16a34a;">{{ $payload['active_count'] ?? 78 }}</div>
                            <div style="font-size: 10px; color: #15803d;">{{ $payload['active_pct'] ?? 59 }}% active</div>
                        </td>
                        <td width="33%" style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; padding: 10px; text-align: center;">
                            <div style="font-size: 10px; font-weight: 700; color: #92400e; text-transform: uppercase;">Dormant</div>
                            <div style="font-size: 20px; font-weight: 800; color: #d97706;">{{ $payload['dormant_count'] ?? 50 }}</div>
                            <div style="font-size: 10px; color: #b45309;">Idle &ge; 1 day</div>
                        </td>
                    </tr>
                    <tr>
                        <td width="33%" style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 10px; text-align: center;">
                            <div style="font-size: 10px; font-weight: 700; color: #991b1b; text-transform: uppercase;">Not Reported</div>
                            <div style="font-size: 20px; font-weight: 800; color: #dc2626;">{{ $payload['nr_count'] ?? 5 }}</div>
                        </td>
                        <td width="33%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; text-align: center;">
                            <div style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase;">Labour</div>
                            <div style="font-size: 20px; font-weight: 800; color: #0f172a;">{{ number_format($payload['total_labour'] ?? 1447) }}</div>
                        </td>
                        <td width="33%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; text-align: center;">
                            <div style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase;">Machinery</div>
                            <div style="font-size: 20px; font-weight: 800; color: #0f172a;">{{ $payload['total_machinery'] ?? 122 }}</div>
                        </td>
                    </tr>
                </table>

                <!-- Priority Status Lines -->
                <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 14px; margin-bottom: 20px;">
                    <div style="font-size: 12px; font-weight: 700; color: #0f172a; margin-bottom: 8px; text-transform: uppercase;">
                        Completion Priorities Overview
                    </div>
                    @foreach($payload['priorities'] ?? [] as $pri)
                    <div style="font-size: 12px; margin-bottom: 4px; padding-bottom: 4px; border-bottom: 1px solid #f1f5f9;">
                        <strong style="color: #0f172a;">Priority {{ $pri['code'] }}:</strong>
                        <span style="color: #16a34a; font-weight: bold;">{{ $pri['active'] }} active</span>,
                        <span style="color: #d97706;">{{ $pri['dormant'] }} dormant</span>,
                        <span style="color: #dc2626;">{{ $pri['nr'] }} NR</span> &bull;
                        <span style="color: {{ $pri['days_left'] < 0 ? '#dc2626' : '#475569' }};">
                            {{ $pri['days_left'] < 0 ? abs($pri['days_left']) . ' days overdue' : $pri['days_left'] . ' days left' }}
                        </span>
                    </div>
                    @endforeach
                </div>

                <!-- Top Critical Idle Sites (7+ days) -->
                @php
                    $criticalSites = collect($payload['register_rows'] ?? [])
                        ->where('idle_band', 'critical')
                        ->take(8);
                @endphp
                @if($criticalSites->isNotEmpty())
                <div style="margin-bottom: 20px;">
                    <div style="font-size: 12px; font-weight: 700; color: #b91c1c; margin-bottom: 6px; text-transform: uppercase;">
                        Top Critical Dormant Sites (Idle &ge; 7 Days)
                    </div>
                    <table width="100%" cellpadding="6" cellspacing="0" style="font-size: 11px; border-collapse: collapse; border: 1px solid #fee2e2;">
                        <tr style="background-color: #fef2f2; color: #991b1b; text-align: left; font-weight: 700;">
                            <th style="border-bottom: 1px solid #fee2e2;">Pri</th>
                            <th style="border-bottom: 1px solid #fee2e2;">Village</th>
                            <th style="border-bottom: 1px solid #fee2e2;">Tehsil</th>
                            <th style="border-bottom: 1px solid #fee2e2;">Contractor</th>
                            <th style="border-bottom: 1px solid #fee2e2; text-align: right;">Idle Days</th>
                        </tr>
                        @foreach($criticalSites as $cs)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td><strong>{{ $cs['priority_code'] ?? '—' }}</strong></td>
                            <td>{{ $cs['village'] }}</td>
                            <td>{{ $cs['tehsil'] }}</td>
                            <td>{{ $cs['contractor'] }}</td>
                            <td style="text-align: right; color: #b91c1c; font-weight: bold;">{{ $cs['status_label'] }}</td>
                        </tr>
                        @endforeach
                    </table>
                </div>
                @endif

                <!-- Call to action button -->
                <div style="text-align: center; margin: 24px 0 12px 0;">
                    <a href="{{ route('dashboard', ['date' => $payload['report_date'] ?? $date]) }}" 
                       style="background-color: #059669; color: #ffffff; text-decoration: none; font-size: 13px; font-weight: 700; padding: 12px 24px; border-radius: 6px; display: inline-block;">
                        Open Live Web Dashboard &rarr;
                    </a>
                </div>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="background-color: #f8fafc; padding: 16px 24px; border-top: 1px solid #e2e8f0; font-size: 11px; color: #64748b; text-align: center;">
                This automated report was compiled and dispatched at 14:00 PKT by the PRMSC Progress & Reporting System.<br>
                Please do not reply directly to this automated email.
            </td>
        </tr>
    </table>
</body>
</html>
