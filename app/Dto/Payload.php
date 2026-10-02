<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enums\Type;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

final readonly class Payload
{
    public function __construct(
        public int $templateId,
        public int $store,
        public Type $type,
        public ?string $impressionDate,
        public array $payloads,
    ) {}

    public static function fromArray(array $data): self
    {


        return new self(
            templateId: (int) $data['template_id'],
            store: (int) $data['store'],
            type: Type::from($data['type']),
            impressionDate: $data['impression_date'] ? $data['impression_date'] : null,
            payloads: array_map(fn(array $payload): PayloadProduct => PayloadProduct::fromArray(
                $payload,
            ), $data['payload']),
        );
    }

    /** IDs de ETIQUETA_PLACAS_RESULTADO enviados nos produtos */
    public function resultIds(): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn(PayloadProduct $product): ?int => $product->nameplate_label_printing,
            $this->payloads,
        ))));
    }

    public function withClubMaxPrice(array $clubIds): self
    {
        if ($clubIds === []) {
            return $this;
        }

        return new self(
            templateId: $this->templateId,
            store: $this->store,
            type: $this->type,
            impressionDate: $this->impressionDate,
            payloads: array_map(fn(PayloadProduct $product): PayloadProduct => in_array(
                $product->nameplate_label_printing,
                $clubIds,
                true,
            ) ? $product->withClubMaxPrice() : $product, $this->payloads),
        );
    }

    public function toArray(): array
    {
        return [
            'template_id' => $this->templateId,
            'store' => $this->store,
            'type' => $this->type->value,
            'impression_date' => $this->resolveImpressionLabel(),
        ];
    }

    public function toAttributes(): array
    {
        return [
            'TEMPLATE_ID' => $this->templateId,
            'LOJA' => $this->store,
            'DATA_REQUISICAO' => now()->format('d-m-Y'),
            'HORA_REQUISICAO' => now()->format('H:i'),
            'REQUISICAO' => Str::uuid(),
            'TOTAL_PRODUTOS' => count($this->payloads),
        ];
    }

    private function resolveImpressionLabel(): ?string
    {
        return filled($this->impressionDate) ? "{$this->impressionDate}" : null;
    }
}
