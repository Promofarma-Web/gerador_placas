<?php

namespace App\Console\Commands;

use App\Events\StorePapersGenerated;
use App\Mail\RecoverProductsFailed;
use App\Models\DailyProducts;
use App\Services\Generetor\StorePapersGenerator;
use Illuminate\Concurrency\ProcessDriver;
use Illuminate\Console\Command;
use Illuminate\Process\Factory as ProcessFactory;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RecoverProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:recover
        {--loja= : Lojas separadas por vírgula (ex: 1,2,3); sem a opção, todas as lojas com produtos vigentes}
        {--quantity= : Quantidade de etiquetas por folha (padrão: 25)}
        {--paralelo=3 : Quantidade de lojas processadas ao mesmo tempo}
        {--agrupar : Envia uma única notificação por loja com todos os PDFs gerados}
        {--todos : Ignora os produtos já gerados e gera novamente todos os produtos da loja}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /** Erros da execução, enviados por e-mail ao final */
    private array $failures = [];

    public function handle()
    {
        /** Cache de imagens desta execução: o mesmo conteúdo em lojas diferentes gera uma única imagem */
        $cacheDir = storage_path('app/image-cache/' . Str::uuid());
        File::ensureDirectoryExists($cacheDir);

        try {
            $this->process($cacheDir);
        } catch (\Throwable $th) {
            $this->error('Erro inesperado: ' . $th->getMessage());
            $this->failures[] = 'Erro inesperado: ' . $this->describe($th);
        } finally {
            File::deleteDirectory($cacheDir);
        }

        if ($this->failures === []) {
            return self::SUCCESS;
        }

        $this->notifyFailures();

        return self::FAILURE;
    }

    private function process(string $cacheDir): void
    {
        $quantity = $this->option('quantity');

        if ($quantity !== null && (! ctype_digit((string) $quantity) || (int) $quantity < 1)) {
            $this->error('A opção --quantity deve ser um inteiro maior que zero');

            return;
        }

        $paralelo = (string) $this->option('paralelo');

        if (! ctype_digit($paralelo) || (int) $paralelo < 1) {
            $this->error('A opção --paralelo deve ser um inteiro maior que zero');

            return;
        }

        $lojas = $this->stores();

        if ($lojas === null) {
            return;
        }

        if ($lojas === []) {
            $this->error('Nenhuma loja encontrada');

            return;
        }

        $perPaper = $quantity !== null ? (int) $quantity : null;
        $todos = (bool) $this->option('todos');

        $this->download($lojas, $todos, $cacheDir);

        /** PDFs gerados por loja; as notificações só saem depois que todas as lojas forem geradas */
        $generatedByStore = [];

        /** O driver padrão do Concurrency mata o processo após 60s; uma loja leva vários minutos */
        $concurrency = new ProcessDriver(new class extends ProcessFactory {
            public function newPendingProcess()
            {
                return parent::newPendingProcess()->forever();
            }
        });

        $this->info('Fase 2: gerando os PDFs - ' . now()->format('d/m/Y H:i:s'));

        /** Cada loja roda em um processo próprio; o lote seguinte começa quando o anterior termina */
        foreach (array_chunk($lojas, (int) $paralelo) as $batch) {
            $this->info('Gerando PDFs das lojas: ' . implode(', ', $batch) . ' - ' . now()->format('d/m/Y H:i:s'));

            $tasks = [];

            foreach ($batch as $loja) {
                $tasks[$loja] = static fn(): array => app(StorePapersGenerator::class)
                    ->handle($loja, $todos, $perPaper, $cacheDir);
            }

            try {
                $results = $concurrency->run($tasks);
            } catch (\Throwable $th) {
                $this->error('Erro no processo das lojas ' . implode(', ', $batch) . ': ' . $th->getMessage());
                $this->failures[] = 'Lojas ' . implode(', ', $batch) . ': ' . $this->describe($th);

                continue;
            }

            foreach ($results as $loja => $result) {
                foreach ($result['messages'] as $message) {
                    $this->info($message);
                }

                foreach ($result['failures'] as $failure) {
                    $this->error($failure);
                    $this->failures[] = $failure;
                }

                if ($result['generated'] !== []) {
                    $generatedByStore[$loja] = $result['generated'];
                }
            }
        }

        $this->notifyStores($generatedByStore, (bool) $this->option('agrupar'));
    }

    /** Lojas do --loja ou, sem a opção, as lojas com produtos vigentes na base; nulo quando o --loja é inválido */
    private function stores(): ?array
    {
        if ($this->option('loja') === null) {
            $lojas = DailyProducts::getStores();
            $this->info('Lojas encontradas na base (' . count($lojas) . '): ' . implode(', ', $lojas));

            return $lojas;
        }

        $lojas = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('loja'))), 'strlen'));

        if ($lojas === [] || array_filter($lojas, fn(string $loja): bool => ! ctype_digit($loja)) !== []) {
            $this->error('A opção --loja deve ser uma lista de inteiros separados por vírgula (ex: 1,2,3)');

            return null;
        }

        return array_map('intval', $lojas);
    }

    /**
     * Fase 1: baixa as imagens de todas as lojas antes de montar qualquer PDF.
     * Roda loja a loja no próprio processo, então cada imagem repetida entre lojas é baixada uma vez só.
     * Erros aqui não interrompem: a fase 2 tenta de novo e reporta.
     */
    private function download(array $lojas, bool $todos, string $cacheDir): void
    {
        $this->info('Fase 1: baixando as imagens - ' . now()->format('d/m/Y H:i:s'));

        $generator = app(StorePapersGenerator::class);

        foreach ($lojas as $loja) {
            try {
                $result = $generator->download($loja, $todos, $cacheDir);
            } catch (\Throwable $th) {
                $this->warn("Loja {$loja}: erro ao baixar as imagens, será tentado na geração do PDF: " . $th->getMessage());

                continue;
            }

            $this->info(sprintf(
                'Loja %d: %d imagens (%d baixadas, %d já baixadas por outra loja, %d falhas) - %s',
                $loja,
                $result['total'],
                $result['downloaded'],
                $result['cached'],
                $result['failed'],
                now()->format('d/m/Y H:i:s'),
            ));
        }
    }

    /** Notifica todas as lojas juntas: uma notificação por loja com --agrupar, senão uma por template */
    private function notifyStores(array $generatedByStore, bool $agrupar): void
    {
        $this->info('Enviando notificações - ' . now()->format('d/m/Y H:i:s'));

        foreach ($generatedByStore as $loja => $generated) {
            $batches = $agrupar ? [$generated] : array_map(fn(array $item): array => [$item], $generated);

            try {
                foreach ($batches as $items) {
                    StorePapersGenerated::dispatch((int) $loja, $items);
                }

                $this->info("Notificação enviada para a loja {$loja} (" . count($generated) . ' templates)');
            } catch (\Throwable $th) {
                $this->error("Erro ao enviar a notificação da loja {$loja}: " . $th->getMessage());
                $this->failures[] = "Notificação da loja {$loja}: " . $this->describe($th);
            }
        }
    }

    /** Envia um e-mail com os erros da execução; falha no envio não derruba o comando */
    private function notifyFailures(): void
    {
        try {
            Mail::to(config('mail.alert_to'))->send(new RecoverProductsFailed($this->options(), $this->failures));
            $this->info('E-mail de erro enviado para ' . config('mail.alert_to'));
        } catch (\Throwable $th) {
            $this->error('Erro ao enviar o e-mail de erro: ' . $th->getMessage());
            report($th);
        }
    }

    private function describe(\Throwable $th): string
    {
        return $th->getMessage() . ' (' . $th->getFile() . ':' . $th->getLine() . ')';
    }
}
