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
        public float $maxPrice,
        public float $salePrice,
        public float $promotionPrice,
        public ?int $percentageDiscount,
        public ?string $initialDate,
        public ?string $finalDate,
        public ?int $buy,
        public ?int $get,
        public ?string $promotionTitle,
        public ?string $expirationDate,
        public ?int $x,
        public ?int $y,
        public ?int $nameplateLabelPrinting,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            product: (int) $data['product'],
            family: (int) $data['family'],
            quantity: (int) $data['quantity'],
            description: (string) $data['description'],
            ean: (string) $data['ean'],
            maxPrice: (float) $data['max_price'],
            salePrice: (float) $data['sail_price'],
            promotionPrice: (float) $data['promotion_price'],
            percentageDiscount: isset($data['percentage_discount']) ? (int) $data['percentage_discount'] : null,
            initialDate: $data['initial_date'] ?? null,
            finalDate: $data['final_date'] ?? null,
            buy: isset($data['buy']) ? (int) $data['buy'] : null,
            get: isset($data['get']) ? (int) $data['get'] : null,
            promotionTitle: $data['promotion_title'] ?? null,
            expirationDate: $data['expiration_date'] ?? null,
            x: isset($data['X']) ? (int) $data['X'] : null,
            y: isset($data['Y']) ? (int) $data['Y'] : null,
            nameplateLabelPrinting: isset($data['nameplate_label_printing'])
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
            'maxPrice' => $this->maxPrice,
            'salePrice' => $this->salePrice,
            'promotion_price' => $this->promotionPrice,
            'percentage_discount' => $this->percentageDiscount,
            'initial_date' => $this->initialDate,
            'final_date' => $this->finalDate,
            'buy' => $this->buy,
            'get' => $this->get,
            'promotion_title' => $this->promotionTitle,
            'expiration_date' => $this->expirationDate,
            'x' => $this->x,
            'y' => $this->y,
            'nameplate_label_printing' => $this->nameplateLabelPrinting,
        ];
    }

    public function toAttributes(): array
    {
        return [
            'PRODUTO' => $this->product,
            'FAMILIA' => $this->family,
            'ETIQUETA_PLACAS_RESULTADO_ID' => $this->nameplateLabelPrinting,
        ];
    }
}
