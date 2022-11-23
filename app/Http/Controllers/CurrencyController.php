<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class CurrencyController extends Controller
{

    function applyFilters($query, $queryArgs)
    {

    }

    function index(Request $request)
    {
        return Currency::get();
    }
}
