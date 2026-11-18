<x-guest-layout subtitle="Verify your email">

    <p class="text-muted">
        Thanks for signing up! We sent a verification link to
        <strong>{{ auth()->user()->email }}</strong>.
        Open your Gmail inbox (check Spam too) and click the link to activate your account.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success">
            A new verification link has been sent to your email address.
        </div>
    @endif

    @if (session('status') == 'verification-send-failed')
        <div class="alert alert-danger">
            We could not send the email right now. Please try "Resend" in a moment.
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="mb-2">
        @csrf
        <button type="submit" class="btn btn-primary w-100 py-2">
            Resend Verification Email
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="text-center">
        @csrf
        <button type="submit" class="btn btn-link btn-sm">Log Out</button>
    </form>

</x-guest-layout>
