@extends('procurement.emails.layout')

@section('title', 'Supplier Updated')
@section('tag', 'Supplier')

@section('content')
    <h2 style="margin-top:0;">Your supplier details were updated</h2>

    <p>
        Hello <strong>{{ $supplier->contact_person }}</strong>, the details of
        <strong>{{ $supplier->name }}</strong> were just updated in our system.
        This email was sent to the email address saved on the supplier.
    </p>

    <table style="border-collapse:collapse; margin:16px 0;">
        <tr><td style="padding:4px 16px 4px 0; color:#666;">Contact person</td><td>{{ $supplier->contact_person }}</td></tr>
        <tr><td style="padding:4px 16px 4px 0; color:#666;">Email</td><td>{{ $supplier->email }}</td></tr>
        <tr><td style="padding:4px 16px 4px 0; color:#666;">Phone</td><td>{{ $supplier->phone }}</td></tr>
        <tr><td style="padding:4px 16px 4px 0; color:#666;">Address</td><td>{{ $supplier->address }}</td></tr>
        <tr><td style="padding:4px 16px 4px 0; color:#666;">Payment terms</td><td>{{ $supplier->payment_terms }}</td></tr>
    </table>

    <p style="margin-top:24px;">
        <a href="{{ route('procurement.suppliers.show', $supplier) }}" style="background-color:#E67E22; color:#ffffff; padding:10px 20px; border-radius:6px; text-decoration:none;">Open supplier</a>
    </p>
@endsection