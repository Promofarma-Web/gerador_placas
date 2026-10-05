<?php

namespace App\Http\Requests;

use App\Dto\Payload;
use App\Enums\Type;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateImageRequest extends FormRequest
{
    // Autoriza a requisação antes de fechar no controller
    public function authorize(): bool
    {
        return true;
    }

    // Valido meu payload de forma completa
    public function rules(): array
    {
        return [
            'template_id' => ['required', 'integer'],
            'store' => ['required', 'integer', 'min:1', 'max:1000'],
            'type' => ['required', 'integer', Rule::enum(Type::class)],
            'impression_date' => ['nullable', 'string'],
            'payload' => ['required', 'array', 'min:1'],
            'payload.*.product' => ['nullable', 'integer'],
            'payload.*.family' => ['required', 'integer'],
            'payload.*.quantity' => ['required', 'integer', 'min:1'],
            'payload.*.description' => ['required', 'string', 'max:255'],
            'payload.*.ean' => ['nullable', 'string', 'max:25'],
            'payload.*.max_price' => ['nullable', 'string'],
            'payload.*.sail_price' => ['nullable', 'string'],
            'payload.*.promotion_price' => ['nullable', 'string'],
            'payload.*.percentage_discount' => ['nullable', 'string'],
            'payload.*.initial_date' => ['nullable', 'string'],
            'payload.*.final_date' => ['nullable', 'string'],
            'payload.*.buy' => ['nullable', 'string'],
            'payload.*.get' => ['nullable', 'string'],
            'payload.*.promotion_title' => ['nullable', 'string'],
            'payload.*.expiration_date' => ['nullable', 'string'],
            'payload.*.x' => ['nullable', 'string'],
            'payload.*.y' => ['nullable', 'string'],
            'payload.*.nameplate_label_printing' => ['nullable', 'string'],
        ];
    }

    public function toData(): Payload
    {
        return Payload::fromArray($this->validated());
    }
}
