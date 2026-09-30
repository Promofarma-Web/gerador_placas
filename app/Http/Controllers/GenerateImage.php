<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\GenerateImageRequest;
use App\Services\Generetor\BatchLabelGenerator;
use App\Services\RequestLogger\RequestLogger;
use Illuminate\Http\JsonResponse;
use Throwable;

final class GenerateImage extends Controller
{
    public function __construct(
        public readonly RequestLogger $logger,
        public readonly BatchLabelGenerator $batch,
    ) {}

    public function __invoke(GenerateImageRequest $request): JsonResponse
    {
        $payload = $request->toData();

        try {
            $logger = $this->logger->handle($payload);

            $results = $this->batch->handle($logger, $payload);

            return response()->json([
                'status' => 'success',
                'template_id' => $payload->templateId,
                'products' => $payload->payloads,
                'type' => $payload->type,
                'pdf' => $results[0] ?? null,
                'pdfs' => $results,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }
}
