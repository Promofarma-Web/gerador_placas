<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Dto\Notification;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class NotificationSender
{
    public function send(Notification $notification): mixed
    {
        $response = Http::baseUrl(config('services.notification.url'))->post(
            '/notifications',
            $notification->toArray(),
        );

        if ($response->failed()) {
            throw new RuntimeException('Failed to request');
        }

        return $response->json();
    }
}
