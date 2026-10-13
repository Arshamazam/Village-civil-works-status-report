<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pending Daily Return Reminder</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 20px; color: #1e293b; line-height: 1.5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0;">
        <tr>
            <td style="background-color: #d97706; padding: 20px; color: #ffffff;">
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #fef3c7;">
                    PRMSC &bull; Submission Deadline Alert
                </div>
                <h1 style="font-size: 18px; font-weight: 800; margin: 4px 0 0 0; color: #ffffff;">
                    Pending Daily Returns Reminder
                </h1>
                <div style="font-size: 12px; color: #fef3c7; margin-top: 4px;">
                    Daily Cut-off: <strong>13:00 PKT</strong>
                </div>
            </td>
        </tr>

        <tr>
            <td style="padding: 24px;">
                <p style="font-size: 14px; margin-top: 0;">
                    Dear Field Officer,<br>
                    You have <strong>{{ $pendingSites->count() }} sites</strong> in your assigned tehsil(s) that have not yet been submitted for today ({{ \Carbon\Carbon::parse($date)->format('D, d M Y') }}).
                </p>

                <p style="font-size: 13px; color: #475569;">
                    Please complete and submit Form DSM-1 returns before the <strong>13:00 PKT</strong> cut-off to prevent sites being recorded as "Not Reported".
                </p>

                <table width="100%" cellpadding="6" cellspacing="0" style="font-size: 12px; border-collapse: collapse; border: 1px solid #e2e8f0; margin: 16px 0;">
                    <tr style="background-color: #f1f5f9; text-align: left; font-weight: 700; color: #475569;">
                        <th style="border-bottom: 1px solid #e2e8f0;">Site Code</th>
                        <th style="border-bottom: 1px solid #e2e8f0;">Village</th>
                        <th style="border-bottom: 1px solid #e2e8f0;">Tehsil</th>
                        <th style="border-bottom: 1px solid #e2e8f0;">Package</th>
                    </tr>
                    @foreach($pendingSites as $site)
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="font-family: monospace; font-weight: bold;">{{ $site->site_code }}</td>
                        <td>{{ $site->village_name }}</td>
                        <td>{{ $site->tehsil->name ?? 'N/A' }}</td>
                        <td>{{ $site->package->code ?? 'N/A' }}</td>
                    </tr>
                    @endforeach
                </table>

                <div style="text-align: center; margin-top: 24px;">
                    <a href="{{ route('field.today') }}" 
                       style="background-color: #d97706; color: #ffffff; text-decoration: none; font-size: 13px; font-weight: 700; padding: 12px 24px; border-radius: 6px; display: inline-block;">
                        Open Daily Return Form &rarr;
                    </a>
                </div>
            </td>
        </tr>

        <tr>
            <td style="background-color: #f8fafc; padding: 14px 24px; border-top: 1px solid #e2e8f0; font-size: 11px; color: #64748b; text-align: center;">
                PRSWSSP Field Monitoring Cell &bull; Automated notification at 12:00 PKT.
            </td>
        </tr>
    </table>
</body>
</html>
