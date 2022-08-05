<?php

namespace App\Models;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model {
    /**
     * Get all of the appendable values that are arrayable.
     *
     * @return array
     */
    protected function getArrayableAppends()
    {
        if (! count($this->appends)) {
            return [];
        }
        return $this->getArrayableItems(
            array_combine($this->appends, array_map(function($append){
                return $this->mutateAttributeForArray($append, null);
            }, $this->appends))
        );
    }

    /**
     * Get an attribute array of all arrayable attributes.
     *
     * @return array
     */
    protected function getArrayableAttributes()
    {
        // If an attribute is a date, we will cast it to a string after converting it
        // to a DateTime / Carbon instance. This is so we will get some consistent
        // formatting while accessing attributes vs. arraying / JSONing a model.
        $attributes = $this->addDateAttributesToArray(
            $attributes = $this->getAttributes()
        );

        $attributes = $this->addMutatedAttributesToArray(
            $attributes, $mutatedAttributes = $this->getMutatedAttributes()
        );

        // Next we will handle any casts that have been setup for this model and cast
        // the values to their appropriate type. If the attribute has a mutator we
        // will not perform the cast on those attributes to avoid any confusion.
        $attributes = $this->addCastAttributesToArray(
            $attributes, $mutatedAttributes
        );

        return $this->getArrayableItems($attributes);
    }

    /**
     * Convert the model's attributes to an array.
     *
     * @return array
     */
    public function attributesToArray()
    {
        return $this->getArrayableAttributes() + $this->getArrayableAppends();
    }

    private function getArrayableItemsRecursive($values, $visible, $hidden){
        $output = [];
        foreach($values as $k => $v) {
            if(!in_array($k, $hidden)){
                if(empty($visible) || in_array($k, $visible)){
                    $output[$k] = $v;
                }
                else {
                    if($v instanceof Arrayable){
                        $v = $v->toArray();
                    }

                    if(!is_array($v)) continue;
                    
                    if(key_exists($k, $visible)){
                        $key = $k;
                    }
                    else if(key_exists("*", $visible)){
                        $key = "*";
                    }

                    if(isset($key)){
                        $closureOrArray = $visible[$key];
                        $output[$k] = !is_string($closureOrArray) && $closureOrArray instanceof Closure ?
                            $closureOrArray->bindTo($this)($v) :
                            collect($this->getArrayableItemsRecursive($v, $visible[$key]??[], $hidden[$key]??[]));
                    }
                }
            }
        }
        return $output;
    }

    protected function getArrayableItems(array $values)
    {
        return $this->getArrayableItemsRecursive($values, $this->getVisible(), $this->getHidden());
    }
}