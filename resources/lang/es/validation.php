<?php

return [
    'required' => "El campo ':attribute' es requerido.",
    'required_if' => "El campo ':attribute' es requerido cuando el campo ':other' es :value.",
    "before_or_equal" => "El campo ':attribute' debe ser una fecha anterior o igual a :date.",
    'current_password' => "La contraseña es incorrecta.",
    'min' => [
        'numeric' => "El campo ':attribute' debe ser menor o igual que :min",
        'file' => "El campo ':attribute' debe tener un tamaño de al menos :min kilobytes.",
        'string' => "El campo ':attribute' debe contener al menos :min caracteres.",
        'array' => "El campo ':attribute' debe contener al menos :min elementos."
    ],
    'exists' => 'El :attribute seleccionado es inválido.',
    'email' => 'El :attribute es inválido.',
    'unique' => 'El :attribute ya está en uso.',
    'regex' => 'El formato del :attribute es inválido.',


    'attributes' => [
        'password_confirmation' => 'confirmación de la contraseña',
        'password' => 'contraseña',
        'current_password' => 'contraseña actual',
        'numero' => 'número',
        'manzana_id' => 'manzana',
        'cliente_id' => 'cliente',
        "numero_transaccion" => "n.º de transacción",
        "username" => "nombre de usuario",
        "email" => "correo electrónico"
    ],
];