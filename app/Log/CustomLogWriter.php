<?php

namespace App\Log;

use Illuminate\Http\Request;

class CustomLogWriter extends \Spatie\HttpLogger\DefaultLogWriter {
    function getMessage(Request $request)
    {
        $data = parent::getMessage($request);
        return [
            'user_id' => $request->user() ? $request->user()->id : 'guest',
            'client_ip_address' => $request->ip(),
        ] + $data;
    }

    function formatMessage(array $message)
    {
        $formatedMessage = parent::formatMessage($message);
        return "$formatedMessage - IP: {$message['client_ip_address']} - UserId: {$message["user_id"]}";
    }
}