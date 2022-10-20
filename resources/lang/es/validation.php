<?php

return [
    'required' => "El campo ':attribute' es requerido.",
    'required_if' => "El campo ':attribute' es requerido cuando el campo ':other' es :value.",
    "before_or_equal" => "El campo ':attribute' debe ser una fecha anterior o igual a :date.",
    'current_password' => "La contraseña es incorrecta.",


    'attributes' => [
        'password_confirmation' => 'confirmación de la contraseña',
        'password' => 'contraseña',
        'current_password' => 'contraseña actual',
        'numero' => 'número',
        'manzana_id' => 'manzana',
        'cliente_id' => 'cliente',
        "numero_transaccion" => "n.º de transacción"
    ],

    'The :attribute must contain at least one uppercase and one lowercase letter.' => "El campo ':attribute' debe contener al menos una mayuscula y una minuscrula",
    'The :attribute must contain at least one letter.' => "El campo ':attribute' debe contener al menos una letra." ,
    'The :attribute must contain at least one symbol.' => "El campo ':attribute' debe contener al menos un caracter especial (E.g. %&/()=?).",
    'The :attribute must contain at least one number.' => "El campo ':attribute' debe contener al menos un numero."

];