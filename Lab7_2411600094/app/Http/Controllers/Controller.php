<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    /*
     * Lab 7 Part 2 Step 4: Laravel 11+ ships this class empty, so $this->authorize()
     * (which the purchase order controller uses everywhere) comes from the
     * AuthorizesRequests trait added here. Gate::authorize() used in Part 1
     * needs nothing — it is a facade call.
     */
    use AuthorizesRequests;
}
