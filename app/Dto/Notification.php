<?php

declare(strict_types=1);

namespace App\Dto;

use App\Models\RequestGeneratorImage;
use Illuminate\View\View;

final readonly class Notification
{
    public function __construct(
        public string $title,
        public View|string $content,
        public int $categoryId,
        public int $userId,
        public array $recipientIds,
    ) {}

    public static function fromLogger(RequestGeneratorImage $logger, View $content): self
    {
        return new self(
            title: "Precificação - Loja {$logger->LOJA}",
            content: $content,
            categoryId: 13,
            userId: 13,
            recipientIds: [$logger->LOJA],
        );
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'content' => $this->content instanceof View ? $this->content->render() : $this->content,
            'category_id' => $this->categoryId,
            'user_id' => $this->userId,
            'recipient_ids' => [...$this->recipientIds],
        ];
    }
}
