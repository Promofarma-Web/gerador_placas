<?php


namespace App\Console\Commands;

use Illuminate\Support\Facades\Http;


class SendNotification
{

    public function Notification($dados)
    {

        $response = Http::post('http://notificacao_http:80/api/v1/notifications', $dados);


        return $response;
    }
}
