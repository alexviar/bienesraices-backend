<?php

/** @var FilesystemAdapter $disk */

use App\Models\Credito;
use App\Models\Venta;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\Storage;

dataset("planes_pago", function (){

    // function attachPlanPago($credito, $filename){
    //     foreach (read_csv($filename) as $row) {
    //         $credito->cuotas()->create([
    //             "numero" => $row[0],
    //             "vencimiento" => $row[1],
    //             "importe" => $row[3],
    //             "saldo" => (string) BigDecimal::of($row[3])->plus($row[4])->toScale(2, RoundingMode::HALF_UP),
    //             "pago_extra" => $row[4],
    //             "saldo_capital" => $row[7]
    //         ]);
    //     }
    // };

    function factory(){
        $credito = Credito::factory([
            "plazo" => 48,
            "periodo_pago" => 1,
            "dia_pago" => 1,
        ])->for(Venta::factory([
            "fecha" => "2022-02-28",
            "moneda" => "USD",
            "importe" => "10530.96",
        ])->credito(), "creditable")->create();
        $credito->build();
        return $credito;
    };

    // function factory2(){
    //     $credito = Credito::factory([
    //         "plazo" => 48,
    //         "periodo_pago" => 1,
    //         "dia_pago" => 1,
    //     ])->for(Venta::factory([
    //         "fecha" => "2022-02-28",
    //         "moneda" => "USD",
    //         "importe" => "10530.96",
    //     ])->credito(), "creditable")->create();

    //     /** @var FilesystemAdapter $disk */
    //     $disk = Storage::disk("tests");
    //     attachPlanPago($credito, $disk->path("Feature/PagoExtra/csv/plan_pagos_1.csv"));
    //     $credito->pagosExtras()->create([
    //         "importe" => "100",
    //         "periodo" => 5,
    //         "tipo_ajuste" => 1
    //     ]);
    //     return $credito;
    // };

    return [
        // #region Pago extra simple {periodo: 5, importe: 100}
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");
        //     return [
        //         // "testDescription" => "registra un pago extra con un importe de 100 en el periodo 5",
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 100,
        //                 "periodo" => 5
        //             ]
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_1.csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");
        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 100,
        //                 "periodo" => 5,
        //                 "tipo_ajuste" => 2
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_2.csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");
        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 100,
        //                 "periodo" => 5,
        //                 "tipo_ajuste" => 3
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_2.csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");
        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 100,
        //                 "periodo" => 5,
        //                 "tipo_ajuste" => 4
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_2.csv")
        //     ];
        // },
        // #endregion

        // #region Pago extra simple {periodo: 14, importe: 1100}
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");
        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 1
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_1(2).csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");
        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 2
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_2(2).csv")
        //     ];
        // },        
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");
        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 3
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_3.csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");
        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 4
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_4.csv")
        //     ];
        // },
        // #endregion
    
        // #region Multiples pagos extra {periodo: 5, importe: 100, tipo_ajuste: 1}, {periodo: 14, importe: 1100}
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");
        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 100,
        //                 "periodo" => 5
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_1_1.csv")
        //     ];
        // },    
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");
        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14
        //             ],
        //             [
        //                 "importe" => 100,
        //                 "periodo" => 5,
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_1_1.csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 100,
        //                 "periodo" => 5,
        //                 "tipo_ajuste" => 1
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 2
        //             ]
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_1_2.csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 2
        //             ],
        //             [
        //                 "importe" => 100,
        //                 "periodo" => 5,
        //                 "tipo_ajuste" => 1
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_1_2.csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 100,
        //                 "periodo" => 5,
        //                 "tipo_ajuste" => 1
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 3
        //             ]
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_1_3.csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 3
        //             ],
        //             [
        //                 "importe" => 100,
        //                 "periodo" => 5,
        //                 "tipo_ajuste" => 1
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_1_3.csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 100,
        //                 "periodo" => 5,
        //                 "tipo_ajuste" => 1
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 4
        //             ]
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_1_4.csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 4
        //             ],
        //             [
        //                 "importe" => 100,
        //                 "periodo" => 5,
        //                 "tipo_ajuste" => 1
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_1_4.csv")
        //     ];
        // },
        // #endregion

        // #region Multiples pagos extra {periodo: 14, importe: 1100, tipo_ajuste: 2}, {periodo: 15, importe: 1000}
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");
        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1000,
        //                 "periodo" => 15,
        //                 "tipo_ajuste" => 1
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 2
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_2_1.csv")
        //     ];
        // },    
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");
        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 2
        //             ],
        //             [
        //                 "importe" => 1000,
        //                 "periodo" => 15,
        //                 "tipo_ajuste" => 1
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_2_1.csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1000,
        //                 "periodo" => 15,
        //                 "tipo_ajuste" => 2
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 2
        //             ]
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_2_2.csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 2
        //             ],
        //             [
        //                 "importe" => 1000,
        //                 "periodo" => 15,
        //                 "tipo_ajuste" => 2
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_2_2.csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1000,
        //                 "periodo" => 15,
        //                 "tipo_ajuste" => 3
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 2
        //             ]
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_2_3.csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 2
        //             ],
        //             [
        //                 "importe" => 1000,
        //                 "periodo" => 15,
        //                 "tipo_ajuste" => 3
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_2_3.csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1000,
        //                 "periodo" => 15,
        //                 "tipo_ajuste" => 4
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 2
        //             ]
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_2_4.csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 2
        //             ],
        //             [
        //                 "importe" => 1000,
        //                 "periodo" => 15,
        //                 "tipo_ajuste" => 4
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_2_4.csv")
        //     ];
        // },        
        // #endregion

        // #region Multiples pagos extra {periodo: 14, importe: 1100, tipo_ajuste: 3}, {periodo: 16, importe: 500}
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");
        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 500,
        //                 "periodo" => 16,
        //                 "tipo_ajuste" => 1
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 3
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_3_1.csv")
        //     ];
        // },    
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");
        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 3
        //             ],
        //             [
        //                 "importe" => 500,
        //                 "periodo" => 16,
        //                 "tipo_ajuste" => 1
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_3_1.csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 500,
        //                 "periodo" => 16,
        //                 "tipo_ajuste" => 2
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 3
        //             ]
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_3_2.csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 3
        //             ],
        //             [
        //                 "importe" => 500,
        //                 "periodo" => 16,
        //                 "tipo_ajuste" => 2
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_3_2.csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 500,
        //                 "periodo" => 16,
        //                 "tipo_ajuste" => 3
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 3
        //             ]
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_3_3.csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 3
        //             ],
        //             [
        //                 "importe" => 500,
        //                 "periodo" => 16,
        //                 "tipo_ajuste" => 3
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_3_3.csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 500,
        //                 "periodo" => 16,
        //                 "tipo_ajuste" => 4
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 3
        //             ]
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_3_4.csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 3
        //             ],
        //             [
        //                 "importe" => 500,
        //                 "periodo" => 16,
        //                 "tipo_ajuste" => 4
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_3_4.csv")
        //     ];
        // },        
        // #endregion

        // #region Multiples pagos extra {periodo: 14, importe: 1100, tipo_ajuste: 4}, {periodo: 16, importe: 500}
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");
        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 500,
        //                 "periodo" => 16,
        //                 "tipo_ajuste" => 1
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 4
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_4_1.csv")
        //     ];
        // },    
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");
        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 4
        //             ],
        //             [
        //                 "importe" => 500,
        //                 "periodo" => 16,
        //                 "tipo_ajuste" => 1
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_4_1.csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 500,
        //                 "periodo" => 16,
        //                 "tipo_ajuste" => 2
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 4
        //             ]
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_4_2.csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 4
        //             ],
        //             [
        //                 "importe" => 500,
        //                 "periodo" => 16,
        //                 "tipo_ajuste" => 2
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_4_2.csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 500,
        //                 "periodo" => 16,
        //                 "tipo_ajuste" => 3
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 4
        //             ]
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_4_3.csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 4
        //             ],
        //             [
        //                 "importe" => 500,
        //                 "periodo" => 16,
        //                 "tipo_ajuste" => 3
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_4_3.csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 500,
        //                 "periodo" => 16,
        //                 "tipo_ajuste" => 4
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 4
        //             ]
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_4_4.csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 4
        //             ],
        //             [
        //                 "importe" => 500,
        //                 "periodo" => 16,
        //                 "tipo_ajuste" => 4
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_4_4.csv")
        //     ];
        // },        
        // #endregion

        // #region Multiples pagos extra en el mismo periodo {periodo: 14, importe: 1100}, {periodo: 14, importe: 1100}
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 1
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 1
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_1_1(2).csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 1
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 2
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_1_2(2).csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 1
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 3
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_1_3(2).csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 1
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 4
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_1_4(2).csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 2
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 1
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_2_1(2).csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 2
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 2
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_2_2(2).csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 2
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 3
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_2_3(2).csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 2
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 4
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_2_4(2).csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 3
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 1
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_3_1(2).csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 3
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 2
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_3_2(2).csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 3
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 3
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_3_3(2).csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 3
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 4
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_3_4(2).csv")
        //     ];
        // },        
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 4
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 1
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_4_1(2).csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 4
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 2
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_4_2(2).csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 4
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 3
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_4_3(2).csv")
        //     ];
        // },
        // function() {
        //     /** @var FilesystemAdapter $disk */
        //     $disk = Storage::disk("tests");

        //     return [
        //         "credito" => factory(),
        //         "requests" => [
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 4
        //             ],
        //             [
        //                 "importe" => 1100,
        //                 "periodo" => 14,
        //                 "tipo_ajuste" => 4
        //             ],
        //         ],
        //         "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_4_4(2).csv")
        //     ];
        // },
        // #endregion

        function() {
            /** @var FilesystemAdapter $disk */
            $disk = Storage::disk("tests");

            return [
                "credito" => factory(),
                "requests" => [
                    [
                        "importe" => 100,
                        "periodo" => 19,
                        "tipo_ajuste" => 2
                    ],
                    [
                        "importe" => 100,
                        "periodo" => 18,
                        "tipo_ajuste" => 4
                    ],
                    [
                        "importe" => 1100,
                        "periodo" => 14,
                        "tipo_ajuste" => 4
                    ],
                    // [
                    //     "importe" => 100,
                    //     "periodo" => 17,
                    //     "tipo_ajuste" => 4
                    // ],
                    // [
                    //     "importe" => 100,
                    //     "periodo" => 15,
                    //     "tipo_ajuste" => 4
                    // ],
                    // [
                    //     "importe" => 100,
                    //     "periodo" => 16,
                    //     "tipo_ajuste" => 4
                    // ],
                ],
                "filename" => $disk->path("Feature/PagoExtra/csv/plan_pagos_diferido.csv")
            ];
        },
    ];
});