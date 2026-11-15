{{--
    Lab 7 Part 4 Step 3: the shared shell every system e-mail is built in.

    Every rule is written as an inline style attribute on the element it styles,
    because mail clients are the worst browsers in the world: Gmail drops
    classes, Outlook ignores flexbox and some clients strip <style> blocks
    entirely. Nothing here may depend on public/css/custom.css — the palette is
    copied instead, which is the only way a 2003-era client still shows the
    RogieMart pink.

    Child views fill @yield('heading') and @section('content').
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject ?? config('app.name') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#FFE5EC; color:#5a3d4a; font-family:'Segoe UI', Helvetica, Arial, sans-serif; font-size:15px; line-height:1.5;">

    {{-- 640 is the width that survives both a desktop pane and a phone: wider tables get scaled down and the numbers turn to mush. --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FFE5EC; padding:24px 12px;">
        <tr>
            <td align="center">

                <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="width:640px; max-width:100%; background-color:#ffffff; border:1px solid #FFC2D1; border-radius:10px;">

                    {{-- Header --}}
                    <tr>
                        <td style="background-color:#FB6F92; padding:18px 28px; border-radius:10px 10px 0 0;">
                            <span style="color:#ffffff; font-size:20px; font-weight:bold; letter-spacing:.3px;">{{ config('app.name') }}</span>
                            <br>
                            <span style="color:#FFE5EC; font-size:12px;">Inventory &amp; procurement notifications</span>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:26px 28px 8px 28px;">

                            @hasSection('heading')
                                <div style="font-size:19px; font-weight:bold; color:#330033; margin:0 0 14px 0;">
                                    @yield('heading')
                                </div>
                            @endif

                            @yield('content')

                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:18px 28px 22px 28px; border-top:1px solid #FFC2D1; margin-top:24px; background-color:#FFE5EC; border-radius:0 0 10px 10px;">
                            <span style="font-size:12px; color:#8a6b78;">
                                Automatic message from the {{ config('app.name') }} stock system — replying to this address will not reach anyone.<br>
                                {{ now()->format('F j, Y, g:i a') }} ·
                                <a href="{{ route('dashboard') }}" style="color:#C9184A; text-decoration:none;">open the dashboard</a>
                            </span>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
