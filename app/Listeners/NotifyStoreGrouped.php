<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Dto\Notification;
use App\Events\StorePapersGenerated;
use App\Query\NotificationPaperQuery;
use App\Query\PendingDownloadQuery;
use App\Services\Notification\NotificationSender;

final class NotifyStoreGrouped
{
    public function __construct(
        public readonly NotificationSender $sender,
    ) {}

    public function handle(StorePapersGenerated $event): void
    {
        $registros = array_map(fn(array $item): array => [
            'paths' => $item['paths'],
            'folhas' => NotificationPaperQuery::getPapersByRequest($item['logger']),
        ], $event->items);

        $payload = Notification::fromStore(
            store: $event->store,
            content: view('notification-content', [
                'registros' => $registros,
                'pendentes' => PendingDownloadQuery::getByStore($event->store),
            ]),
        );

        $this->sender->send($payload);
    }
}
