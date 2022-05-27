<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InitialLoadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::transaction(function(){
            $this->loadProyectos();
            $this->loadManzanas();
            $this->loadLotes();
        });
    }

    private function loadProyectos(){
        $disk = Storage::disk("csv");
        $files = $disk->files("Proyectos");
        foreach($files as $filename){
            if(Str::endsWith($filename, ".csv")){
                $filename = str_replace("\\", "/", $disk->path($filename));
                DB::select("LOAD DATA LOCAL INFILE '$filename' INTO TABLE proyectos\r\n"
                . "FIELDS TERMINATED BY '\\,' OPTIONALLY ENCLOSED BY '\"'\r\n"
                . "IGNORE 1 LINES\r\n"
                . "(legacy_id, nombre, socio, @latitud, @longitud, moneda, precio_mt2, precio_reserva, redondeo, cuota_inicial, tasa_interes, tasa_mora)\r\n"
                . "SET created_at=CURRENT_TIMESTAMP,\r\n"
                . "    updated_at= CURRENT_TIMESTAMP,\r\n"
                . "    ubicacion=POINT(@longitud, @latitud)");
            }
        }
    }

    private function loadManzanas(){
        $disk = Storage::disk("csv");
        $files = $disk->files("Manzanas");
        foreach($files as $filename){
            if(Str::endsWith($filename, ".csv")){
                $filename = str_replace("\\", "/", $disk->path($filename));
                DB::select("LOAD DATA LOCAL INFILE '$filename' INTO TABLE manzanas\r\n"
                . "FIELDS TERMINATED BY '\\,' OPTIONALLY ENCLOSED BY '\"'\r\n"
                . "IGNORE 1 LINES\r\n"
                . "(numero, @proyecto_id)\r\n"
                . "SET created_at=CURRENT_TIMESTAMP,\r\n"
                . "    updated_at= CURRENT_TIMESTAMP,\r\n"
                . "    proyecto_id=(SELECT id FROM proyectos WHERE legacy_id=@proyecto_id LIMIT 1)");
            }
        }
    }

    private function loadLotes(){
        $disk = Storage::disk("csv");
        $files = $disk->files("Lotes");
        foreach($files as $filename){
            if(Str::endsWith($filename, ".csv")){
                $filename = str_replace("\\", "/", $disk->path($filename));
                DB::select("LOAD DATA LOCAL INFILE '$filename' INTO TABLE lotes\r\n"
                . "FIELDS TERMINATED BY '\\,' OPTIONALLY ENCLOSED BY '\"'\r\n"
                . "IGNORE 1 LINES\r\n"
                . "(numero, superficie, precio, estado, @manzana_id, @proyecto_id)\r\n"
                . "SET created_at=CURRENT_TIMESTAMP,\r\n"
                . "    updated_at= CURRENT_TIMESTAMP,\r\n"
                . "    manzana_id=(SELECT id FROM manzanas WHERE numero=@manzana_id && proyecto_id=(SELECT id FROM proyectos WHERE legacy_id=@proyecto_id LIMIT 1) LIMIT 1)");
            }
        }
    }
}
