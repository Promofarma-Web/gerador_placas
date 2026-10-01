<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Dto\Notification;
use App\Events\PaperGenerated;
use App\Query\NotificationPaperQuery;
use App\Services\Notification\NotificationSender;

final class NotifyStore
{
    public function __construct(
        public readonly NotificationSender $sender,
    ) {}

    public function handle(PaperGenerated $event): void
    {
        $payload = Notification::fromLogger(
            logger: $event->logger,
            content: view('notification-content', [
                'logger' => $event->logger,
                'paths' => $event->paths,
                'folhas' => NotificationPaperQuery::getPapersByRequest($event->logger),
            ]),
        );

        $this->sender->send($payload);
    }
}
