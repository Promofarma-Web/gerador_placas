<?php

namespace App\Services;

class PdfService
{
    private const IMG_W    = 56;
    private const IMG_H    = 92;
    private const COLS     = 3.5;
    private const ROWS     = 3;
    private const PAGE_W   = 210;
    private const PAGE_H   = 297;
    private const MARGIN_X = (self::PAGE_W - self::COLS * self::IMG_W) / 2.6;
    private const MARGIN_Y = (self::PAGE_H - self::ROWS * self::IMG_H) / 1.8;

    /**
     * Generate a PDF from one or more base64 images.
     * Images are placed left-to-right, top-to-bottom in the grid.
     *
     * @param  string[]  $base64Images
     */
    public function generate(array $base64Images, string $filename): string
    {
        $dir = public_path('img');

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $tmpFiles = [];

      
        try {
            foreach ($base64Images as $index => $base64Image) {
                $tmpFiles[$index] = $this->saveTempImage($base64Image, $dir, $filename . '_tmp_' . $index);
            }

            $filePath = $dir . '/' . $filename . '_' . now()->format('YmdHis') . '.pdf';

            $perPage = self::COLS * self::ROWS;
            $chunks  = array_chunk($tmpFiles, $perPage, true);

            $pdf = new \FPDF('P', 'mm', 'A4');

            foreach ($chunks as $chunk) {
                $pdf->AddPage();

                foreach (array_values($chunk) as $pos => ['path' => $path, 'type' => $type]) {
                    [$x, $y] = $this->cellPosition($pos);
                    $pdf->Image($path, $x, $y, self::IMG_W, self::IMG_H, $type);
                }
            }

            $pdf->Output('F', $filePath);

        } finally {
            foreach ($tmpFiles as ['path' => $path]) {
                if (file_exists($path)) {
                    unlink($path);
                }
            }
        }

        return basename($filePath);
    }


    private function saveTempImage(string $base64Image, string $dir, string $name): array
    {
        if (preg_match('/^data:image\/\w+;base64,/', $base64Image)) {
            $base64Image = preg_replace('/^data:image\/\w+;base64,/', '', $base64Image);
        }

        $imageData = base64_decode($base64Image, true);

        if ($imageData === false || $imageData === '') {
            throw new \RuntimeException("Invalid base64 image data for: {$name}");
        }

        if (str_starts_with($imageData, "\x89PNG\r\n\x1a\n")) {
            $type = 'PNG';
        } elseif (str_starts_with($imageData, "\xFF\xD8\xFF")) {
            $type = 'JPEG';
        } else {
            throw new \RuntimeException("Unsupported or corrupt image data for: {$name}");
        }

        $ext  = $type === 'JPEG' ? 'jpg' : 'png';
        $path = $dir . '/' . $name . '.' . $ext;

        file_put_contents($path, $imageData);

        return ['path' => $path, 'type' => $type];
    }

    private function cellPosition(int $index): array
    {
        $col = $index % self::COLS;
        $row = intdiv($index, self::COLS);

        return [
            self::MARGIN_X + $col * self::IMG_W,
            self::MARGIN_Y + $row * self::IMG_H,
        ];
    }
}
