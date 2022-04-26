<?php

use App\Models\Currency;
use App\Models\DetalleTransaccion;
use App\Models\Transaccion;
use App\Models\User;
use App\Models\ValueObjects\Money;
use App\Models\Venta;
use Illuminate\Support\Arr;
use Tests\TestCase;

test('Registro exitoso', function () {
    /** @var TestCase $this */

    //Venta al contado
    $data = Venta::factory([
        "moneda" => "USD",
        "precio" => "10530.96",
    ])->contado()->withReserva(false)->raw();

    $proyectoId = $data["proyecto_id"];

    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);

    $response->assertCreated();
    $id = $response->json("id");

    // Venta::find($id);

    $this->assertDatabaseHas("ventas", ["estado"=>1] + $data);
    $this->assertDatabaseMissing("cuotas", [
        "venta_id" => $id
    ]);
    //Idealmente se debería testear si el evento de registro de venta es disparado
    $this->assertDatabaseHas("transacciones", [
        "fecha" => $data["fecha"],
        "forma_pago" => 2,
        "moneda" => $data["moneda"],
        "importe" => $data["precio"],
    ]);
    $this->assertDatabaseHas("detalles_transaccion", [
        "transactable_id" => $id,
        "transactable_type" => Venta::class,
        "moneda" => $data["moneda"],
        "importe" => $data["precio"],
    ]);

    //Venta al credito
    $data = Venta::factory([
        "fecha" => "2020/09/15",
        "moneda" => "USD",
        "precio" => "10530.96",
        "plazo" => 48,
        "periodo_pago" => 1,
        "cuota_inicial" => "500",
        "tasa_interes" => "0.1000"
    ])->credito()->withReserva(false)->raw();

    $proyectoId = $data["proyecto_id"];

    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);

    $response->assertCreated();
    $id = $response->json("id");

    // Venta::find($id);

    $this->assertDatabaseHas("ventas", ["estado"=>1] + $data);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2020/10/15", "venta_id" => $id, "numero" => 1, "importe" => "254.41", "saldo_capital" => "9860.14"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2020/11/15", "venta_id" => $id, "numero" => 2, "importe" => "254.41", "saldo_capital" => "9687.90"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2020/12/15", "venta_id" => $id, "numero" => 3, "importe" => "254.41", "saldo_capital" => "9514.22"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2021/01/15", "venta_id" => $id, "numero" => 4, "importe" => "254.41", "saldo_capital" => "9339.1"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2021/02/15", "venta_id" => $id, "numero" => 5, "importe" => "254.41", "saldo_capital" => "9162.52"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2021/03/15", "venta_id" => $id, "numero" => 6, "importe" => "254.41", "saldo_capital" => "8984.46"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2021/04/15", "venta_id" => $id, "numero" => 7, "importe" => "254.41", "saldo_capital" => "8804.92"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2021/05/15", "venta_id" => $id, "numero" => 8, "importe" => "254.41", "saldo_capital" => "8623.88"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2021/06/15", "venta_id" => $id, "numero" => 9, "importe" => "254.41", "saldo_capital" => "8441.34"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2021/07/15", "venta_id" => $id, "numero" => 10, "importe" => "254.41", "saldo_capital" => "8257.27"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2021/08/15", "venta_id" => $id, "numero" => 11, "importe" => "254.41", "saldo_capital" => "8071.67"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2021/09/15", "venta_id" => $id, "numero" => 12, "importe" => "254.41", "saldo_capital" => "7884.52"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2021/10/15", "venta_id" => $id, "numero" => 13, "importe" => "254.41", "saldo_capital" => "7695.81"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2021/11/15", "venta_id" => $id, "numero" => 14, "importe" => "254.41", "saldo_capital" => "7505.53"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2021/12/15", "venta_id" => $id, "numero" => 15, "importe" => "254.41", "saldo_capital" => "7313.67"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2022/01/15", "venta_id" => $id, "numero" => 16, "importe" => "254.41", "saldo_capital" => "7120.21"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2022/02/15", "venta_id" => $id, "numero" => 17, "importe" => "254.41", "saldo_capital" => "6925.14"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2022/03/15", "venta_id" => $id, "numero" => 18, "importe" => "254.41", "saldo_capital" => "6728.44"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2022/04/15", "venta_id" => $id, "numero" => 19, "importe" => "254.41", "saldo_capital" => "6530.10"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2022/05/15", "venta_id" => $id, "numero" => 20, "importe" => "254.41", "saldo_capital" => "6330.11"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2022/06/15", "venta_id" => $id, "numero" => 21, "importe" => "254.41", "saldo_capital" => "6128.45"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2022/07/15", "venta_id" => $id, "numero" => 22, "importe" => "254.41", "saldo_capital" => "5925.11"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2022/08/15", "venta_id" => $id, "numero" => 23, "importe" => "254.41", "saldo_capital" => "5720.08"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2022/09/15", "venta_id" => $id, "numero" => 24, "importe" => "254.41", "saldo_capital" => "5513.34"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2022/10/15", "venta_id" => $id, "numero" => 25, "importe" => "254.41", "saldo_capital" => "5304.87"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2022/11/15", "venta_id" => $id, "numero" => 26, "importe" => "254.41", "saldo_capital" => "5094.67"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2022/12/15", "venta_id" => $id, "numero" => 27, "importe" => "254.41", "saldo_capital" => "4882.72"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2023/01/15", "venta_id" => $id, "numero" => 28, "importe" => "254.41", "saldo_capital" => "4669"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2023/02/15", "venta_id" => $id, "numero" => 29, "importe" => "254.41", "saldo_capital" => "4453.50"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2023/03/15", "venta_id" => $id, "numero" => 30, "importe" => "254.41", "saldo_capital" => "4236.20"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2023/04/15", "venta_id" => $id, "numero" => 31, "importe" => "254.41", "saldo_capital" => "4017.09"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2023/05/15", "venta_id" => $id, "numero" => 32, "importe" => "254.41", "saldo_capital" => "3796.16"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2023/06/15", "venta_id" => $id, "numero" => 33, "importe" => "254.41", "saldo_capital" => "3573.38"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2023/07/15", "venta_id" => $id, "numero" => 34, "importe" => "254.41", "saldo_capital" => "3348.75"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2023/08/15", "venta_id" => $id, "numero" => 35, "importe" => "254.41", "saldo_capital" => "3122.25"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2023/09/15", "venta_id" => $id, "numero" => 36, "importe" => "254.41", "saldo_capital" => "2893.86"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2023/10/15", "venta_id" => $id, "numero" => 37, "importe" => "254.41", "saldo_capital" => "2663.57"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2023/11/15", "venta_id" => $id, "numero" => 38, "importe" => "254.41", "saldo_capital" => "2431.36"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2023/12/15", "venta_id" => $id, "numero" => 39, "importe" => "254.41", "saldo_capital" => "2197.21"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2024/01/15", "venta_id" => $id, "numero" => 40, "importe" => "254.41", "saldo_capital" => "1961.11"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2024/02/15", "venta_id" => $id, "numero" => 41, "importe" => "254.41", "saldo_capital" => "1723.04"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2024/03/15", "venta_id" => $id, "numero" => 42, "importe" => "254.41", "saldo_capital" => "1482.99"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2024/04/15", "venta_id" => $id, "numero" => 43, "importe" => "254.41", "saldo_capital" => "1240.94"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2024/05/15", "venta_id" => $id, "numero" => 44, "importe" => "254.41", "saldo_capital" => "996.87"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2024/06/15", "venta_id" => $id, "numero" => 45, "importe" => "254.41", "saldo_capital" => "750.77"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2024/07/15", "venta_id" => $id, "numero" => 46, "importe" => "254.41", "saldo_capital" => "502.62"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2024/08/15", "venta_id" => $id, "numero" => 47, "importe" => "254.41", "saldo_capital" => "252.4"]);
    $this->assertDatabaseHas("cuotas", ["vencimiento" => "2024/09/15", "venta_id" => $id, "numero" => 48, "importe" => "254.50", "saldo_capital" => "0.00"]);

    //Idealmente se debería testear si el evento de registro de venta es disparado
    $this->assertDatabaseHas("transacciones", [
        "fecha" => $data["fecha"],
        "forma_pago" => 2,
        "moneda" => $data["moneda"],
        "importe" => $data["cuota_inicial"],
    ]);


    $this->assertDatabaseHas("detalles_transaccion", [
        "transactable_id" => $id,
        "transactable_type" => Venta::class,
        "moneda" => $data["moneda"],
        "importe" => $data["cuota_inicial"]
    ]);
});


test('Registro con reserva', function () {
    /** @var TestCase $this */

    //Venta al contado
    $data = Venta::factory([
        "moneda" => "USD",
        "precio" => "10530.96",
    ])->contado()->withReserva()->raw();

    $proyectoId = $data["proyecto_id"];

    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);

    $response->assertCreated();
    $id = $response->json("id");

    $venta = Venta::find($id);

    $this->assertDatabaseHas("transacciones", [
        "fecha" => $venta->fecha,
        "forma_pago" => 2,
        "moneda" => $venta->moneda,
        "importe" => (string) $venta->precio->minus($venta->reserva->importe)->amount,
    ]);
    $this->assertDatabaseHas("detalles_transaccion", [
        "transactable_id" => $id,
        "transactable_type" => Venta::class,
        "moneda" => $venta->moneda,
        "importe" => (string) $venta->precio->minus($venta->reserva->importe)->amount,
    ]);

    // Venta al credito
    $data = Venta::factory([
        "fecha" => "2020/09/15",
        "moneda" => "USD",
        "precio" => "10530.96",
        "reserva_id" => null,
        "plazo" => 48,
        "periodo_pago" => 1,
        "cuota_inicial" => "500",
        "tasa_interes" => "0.1000"
    ])->credito()->withReserva()->raw();

    $proyectoId = $data["proyecto_id"];

    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);

    $response->assertCreated();
    $id = $response->json("id");

    $venta = Venta::find($id);

    // Idealmente se debería testear si el evento de registro de venta es disparado
    $this->assertDatabaseHas("transacciones", [
        "fecha" => $data["fecha"],
        "forma_pago" => 2,
        "moneda" => $venta->moneda,
        "importe" => (string) $venta->cuota_inicial->minus($venta->reserva->importe)->amount,
    ]);


    $this->assertDatabaseHas("detalles_transaccion", [
        "transactable_id" => $id,
        "transactable_type" => Venta::class,
        "moneda" => $venta->moneda,
        "importe" => (string) $venta->cuota_inicial->minus($venta->reserva->importe)->amount,
    ]);
});