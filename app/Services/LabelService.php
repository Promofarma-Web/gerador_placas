<?php

namespace App\Services;

class LabelService
{
    private const PICOTE_G_X  = 28.0; 
    private const PICOTE_G_Y  = 30.0;
    private const PICOTE_P_X  = 7.5;
    private const PICOTE_P_Y  = 10;


    private const MARGIN_X  = self::PICOTE_P_X;
    private const MARGIN_Y  = self::PICOTE_P_Y ;

    private const COLS      = 3;
    private const ROWS      = 9;

    private const GAP_X     = 3;
    private const GAP_Y     = 2.4;

    private const IMG_W     = (self::PICOTE_G_X * 2) - 2;
    private const IMG_H     = (self::PICOTE_G_Y) - 1.4;

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
            $pdf->SetAutoPageBreak(false);

            foreach ($chunks as $chunk) {
                $pdf->AddPage();

                // $this->drawGrid($pdf);

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

    private function cellPosition(int $index): array
    {
        $col = $index % self::COLS;
        $row = intdiv($index, self::COLS);

        $x = self::MARGIN_X + $col * (self::IMG_W + self::GAP_X);
        $y = self::MARGIN_Y + $row * (self::IMG_H + self::GAP_Y);

        return [$x, $y];
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
}