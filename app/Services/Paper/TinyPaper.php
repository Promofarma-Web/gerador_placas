<?php

namespace App\Services\Paper;

use App\Services\Paper\Contracts\PaperInterface;

class TinyPaper implements PaperInterface
{
    private const PAGE_W = 210.0;
    private const PAGE_H = 297.0;

    // Picote (cada quadrado da folha): 2.8cm x 3cm. Fixo — não é variável de ajuste.
    private const PICOTE_G_X = 28.0;
    private const PICOTE_G_Y = 30.0;

    // Margem destacável (serrilhada): 0.8cm em cada lateral, 1.1cm em cima e embaixo. Fixo.
    private const MARGIN_X = 8.0;
    private const MARGIN_Y = 11.0;

    // Fixo: 7 colunas x 9 linhas de picotes por folha.
    private const COLS = 7;
    private const ROWS = 9;

    // Espaço entre picotes: o que sobra da folha depois de descontar margens e picotes fixos.
    private const GAP_X = (self::PAGE_W - (2 * self::MARGIN_X) - (self::COLS * self::PICOTE_G_X)) / (self::COLS - 1);
    private const GAP_Y = (self::PAGE_H - (2 * self::MARGIN_Y) - (self::ROWS * self::PICOTE_G_Y)) / (self::ROWS - 1);

    // Único valor dimensionável: tamanho da imagem impressa dentro do picote.
    private const IMG_W = 27.6;
    private const IMG_H = 28.6;

    // Flag para testes: true exibe o serrilhado da folha, false oculta.
    private const SHOW_GRID = false;

    public function generate(array $base64Images, string $filename): string
    {
        $dir = public_path('img');

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $tmpFiles = [];

        try {
            foreach ($base64Images as $index => $base64Image) {
                $tmpFiles[$index] = $this->saveTempImage($base64Image, $dir, $filename.'_tmp_'.$index);
            }

            $filePath = $dir.'/'.$filename.'_'.now()->format('YmdHis').'.pdf';

            $perPage = self::COLS * self::ROWS;
            $chunks = array_chunk($tmpFiles, $perPage, true);

            $pdf = new \FPDF('P', 'mm', 'A4');
            $pdf->SetAutoPageBreak(false);

            foreach ($chunks as $chunk) {
                $pdf->AddPage();

                if (self::SHOW_GRID) {
                    $this->drawGrid($pdf);
                }

                foreach (array_values($chunk) as $pos => ['path' => $path, 'type' => $type]) {
                    [$x, $y] = $this->imagePosition($pos);
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

    // Posição do picote (quadrado) na folha — depende só do tamanho/espaçamento do picote.
    private function cellPosition(int $index): array
    {
        $col = $index % self::COLS;
        $row = intdiv($index, self::COLS);

        $x = self::MARGIN_X + ($col * (self::PICOTE_G_X + self::GAP_X));
        $y = self::MARGIN_Y + ($row * (self::PICOTE_G_Y + self::GAP_Y));

        return [$x, $y];
    }

    // Posição da imagem — centralizada dentro do picote, com tamanho independente dele.
    private function imagePosition(int $index): array
    {
        [$x, $y] = $this->cellPosition($index);

        $x += (self::PICOTE_G_X - self::IMG_W) / 2;
        $y += (self::PICOTE_G_Y - self::IMG_H) / 2;

        return [$x, $y];
    }

    // Serrilhado da folha inteira: margens destacáveis laterais/superior + linhas internas entre os picotes.
    private function drawGrid(\FPDF $pdf): void
    {
        $pdf->SetDrawColor(150, 150, 150);
        $pdf->SetLineWidth(0.1);

        $this->drawDashedLine($pdf, self::MARGIN_X, 0, self::MARGIN_X, self::PAGE_H, 0.8, 0.8);
        $this->drawDashedLine(
            $pdf,
            self::PAGE_W - self::MARGIN_X,
            0,
            self::PAGE_W - self::MARGIN_X,
            self::PAGE_H,
            0.8,
            0.8,
        );
        $this->drawDashedLine($pdf, 0, self::MARGIN_Y, self::PAGE_W, self::MARGIN_Y, 0.8, 0.8);
        $this->drawDashedLine(
            $pdf,
            0,
            self::PAGE_H - self::MARGIN_Y,
            self::PAGE_W,
            self::PAGE_H - self::MARGIN_Y,
            0.8,
            0.8,
        );

        for ($col = 0; $col < (self::COLS - 1); $col++) {
            $x = self::MARGIN_X + ($col * (self::PICOTE_G_X + self::GAP_X)) + self::PICOTE_G_X + (self::GAP_X / 2);
            $this->drawDashedLine($pdf, $x, 0, $x, self::PAGE_H, 0.8, 0.8);
        }

        for ($row = 0; $row < (self::ROWS - 1); $row++) {
            $y = self::MARGIN_Y + ($row * (self::PICOTE_G_Y + self::GAP_Y)) + self::PICOTE_G_Y + (self::GAP_Y / 2);
            $this->drawDashedLine($pdf, 0, $y, self::PAGE_W, $y, 0.8, 0.8);
        }

        $pdf->SetDrawColor(0, 0, 0);
    }

    private function drawDashedLine(
        \FPDF $pdf,
        float $x1,
        float $y1,
        float $x2,
        float $y2,
        float $dash,
        float $gap,
    ): void {
        $length = sqrt(($x2 - $x1) ** 2 + ($y2 - $y1) ** 2);

        if ($length <= 0) {
            return;
        }

        $dx = ($x2 - $x1) / $length;
        $dy = ($y2 - $y1) / $length;
        $step = $dash + $gap;

        for ($pos = 0; $pos < $length; $pos += $step) {
            $end = min($pos + $dash, $length);

            $pdf->Line(
                $x1 + ($dx * $pos),
                $y1 + ($dy * $pos),
                $x1 + ($dx * $end),
                $y1 + ($dy * $end),
            );
        }
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

        $ext = $type === 'JPEG' ? 'jpg' : 'png';
        $path = $dir.'/'.$name.'.'.$ext;

        file_put_contents($path, $imageData);

        return ['path' => $path, 'type' => $type];
    }
}
