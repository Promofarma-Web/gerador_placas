<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class PayloadProduct
{
    public function __construct(
        public int $product,
        public int $family,
        public int $quantity,
        public string $description,
        public string $ean,
        public string $max_price,
        public string $sail_price,
        public string $promotion_price,
        public ?string $percentage_discount,
        public ?string $initial_date,
        public ?string $final_date,
        public ?string $buy,
        public ?string $get,
        public ?string $promotion_title,
        public ?string $expiration_date,
        public ?string $x,
        public ?string $y,
        public ?int $nameplate_label_printing,
    ) {}

    public static function fromArray(array $data): self
    {


        return new self(
            product: (int) $data['product'],
            family: (int) $data['family'],
            quantity: (int) $data['quantity'],
            description: (string) $data['description'],
            ean: (string) $data['ean'],
            max_price: (string) $data['max_price'],
            sail_price: (string) $data['sail_price'],
            promotion_price: (string) $data['promotion_price'],
            percentage_discount: isset($data['percentage_discount']) ? (string) $data['percentage_discount'] : null,
            initial_date: $data['initial_date'] ?? null,
            final_date: $data['final_date'] ?? null,
            buy: isset($data['buy']) ? (string) $data['buy'] : null,
            get: isset($data['get']) ? (string) $data['get'] : null,
            promotion_title: $data['promotion_title'] ?? null,
            expiration_date: $data['expiration_date'] ?? null,
            x: isset($data['x']) ? (string) $data['x'] : null,
            y: isset($data['y']) ? (string) $data['y'] : null,
            nameplate_label_printing: isset($data['nameplate_label_printing'])
                ? (int) $data['nameplate_label_printing']
                : null,
        );
    }

    public function toArray(): array
    {
        return [
            'product' => $this->product,
            'family' => $this->family,
            'quantity' => $this->quantity,
            'description' => $this->description,
            'ean' => $this->ean,
            'max_price' => $this->max_price,
            'sail_price' => $this->sail_price,
            'promotion_price' => $this->promotion_price,
            'percentage_discount' => $this->percentage_discount,
            'initial_date' => $this->initial_date,
            'final_date' => $this->final_date,
            'buy' => $this->buy,
            'get' => $this->get,
            'promotion_title' => $this->promotion_title,
            'expiration_date' => $this->expiration_date,
            'x' => $this->x,
            'y' => $this->y,
            'nameplate_label_printing' => $this->nameplate_label_printing,
        ];
    }

    public function toAttributes(): array
    {
        return [
            'PRODUTO' => $this->product,
            'FAMILIA' => $this->family,
            'ETIQUETA_PLACAS_RESULTADO_ID' => $this->nameplate_label_printing,
        ];
    }
}
