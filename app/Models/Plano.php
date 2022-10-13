<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Plano extends Model
{
    use HasFactory;

    protected $attributes = [
        "estado" => 1
    ];

    protected $fillable = [
        "titulo",
        "descripcion",
        "estado",
        "import_warnings"
    ];

    protected $hidden = [
        "import_warnings"
    ];

    protected $appends = [
        "is_vigente",
        "is_locked",
        "has_errors",
    ];

    #region Accessors

    public function getIsVigenteAttribute()
    {
        return ($this->estado&1) == 1;
    }

    // public function getIsObsoletoAttribute()
    // {
    //     return ($this->estado&1) == 0;
    // }

    public function getIsLockedAttribute()
    {
        return ($this->estado&2) == 2;
    }

    // public function getIsUnlockedAttribute()
    // {
    //     return ($this->estado&2) == 1;
    // }

    public function getHasErrorsAttribute()
    {
        return $this->import_warnings !== null;
    }
    #endregion

    #region Mutators
    public function setIsVigenteAttribute($value)
    {
        if($value){
            $this->attributes["estado"] |= 1;
        }
        else{
            $this->attributes["estado"] &= 0xFE;
        }
    }

    public function setIsLockedAttribute($value)
    {
        if($value){
            $this->attributes["estado"] |= 2;
        }
        else{
            $this->attributes["estado"] &= 0xFD;
        }
    }
    #endregion

    #region Relationships
    /**
     * @return BelongsTo
     */
    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class);
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function manzanas()
    {
        return $this->hasMany(Manzana::class);
    }

    public function lotes(){
        return $this->hasManyThrough(Lote::class, Manzana::class);
    }
    #endregion

    public function importManzanasYLotesFromCsv($filename){
        $fileContent = file_get_contents($filename);
        $fileContent = str_replace("\r", "", $fileContent);
        file_put_contents($filename, $fileContent);
        $eol = "\n";
        // $eol = substr(json_encode(detectEol($filename, "\n")), 1, -1);

        $filename = str_replace("\\", "\\\\", $filename);
        $planoId = $this->id;

        /**
         * Actualemente LOAD DATA INFILE solo admite string literales como nombre de archivo,
         * esto significa que no puede ser parametrizado o usar una variable para sustituirlo. 
         * @link https://bugs.mysql.com/bug.php?id=39115
         */
        DB::statement(<<<SQL
            LOAD DATA LOCAL INFILE '$filename' INTO TABLE `manzanas`
            CHARACTER SET utf8
            FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"'
            LINES TERMINATED BY '$eol'
            IGNORE 1 LINES
            (`numero`, @c1, @c2, @c3)
            SET `plano_id` = ?
        SQL, [$planoId]);

        // DB::listen(function($query) {
        //     Log::info(
        //         $query->sql,
        //         $query->bindings,
        //         $query->time
        //     );
        // });

        DB::statement(<<<SQL
            LOAD DATA LOCAL INFILE '$filename' INTO TABLE `lotes`
            CHARACTER SET utf8
            FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"'
            LINES TERMINATED BY '$eol'
            IGNORE 1 LINES
            (@manzana, `numero`, `superficie`, @categoria)
            SET `manzana_id` = (SELECT `id` FROM `manzanas` WHERE `plano_id` = ? AND `numero` = @manzana),
                `categoria_id` = (SELECT `id` FROM `categoria_lotes` WHERE `codigo` = @categoria AND `proyecto_id` = ?),
                `estado` = 1
        SQL, [$planoId, $this->proyecto_id]);
        $warning_messages = collect(DB::select('SHOW WARNINGS'))->filter(function($warning){
            return $warning->Code !== 1062;
        });
        $this->import_warnings = !$warning_messages->count() ? null : $warning_messages->toJson();
        $this->update();
    }
}
