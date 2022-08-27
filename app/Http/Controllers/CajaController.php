<?php

namespace App\Http\Controllers;

use App\Models\Cuota;
use App\Models\Currency;
use App\Models\DetalleTransaccion;
use App\Models\Transaccion;
use App\Models\ValueObjects\Money;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class CajaController extends Controller
{
    function index(Request $request)
    {
        $queryArgs = $request->only(["search", "filter", "page"]);
        return $this->buildResponse(Transaccion::with("detalles"), $queryArgs);
    }
}
