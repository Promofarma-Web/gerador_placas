<?php

namespace App\Query;

use App\Enums\PrintOrigin;
use App\Enums\TypePages as TypePagesEnum;
use App\Models\DailyProducts;
use App\Models\FamiliaProduto;
use App\Models\Products;
use App\Models\RequestGeneratorImage;
use App\Models\RequestGeneratorImageProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PdfByStoreQuery
{
    public const HORAS = 72;

    /** Janela das etiquetas sem vigência (gôndola e reduzidas) no Recuperar PDF */
    public const DIAS_SEM_VIGENCIA = 30;

    /** Data/hora da requisição (DATA_REQUISICAO e HORA_REQUISICAO ficam em colunas separadas) */
    private const REQUESTED_AT = 'CAST(RGP.DATA_REQUISICAO AS DATETIME) + CAST(RGP.HORA_REQUISICAO AS DATETIME)';

    /**
     * PDFs da loja com promoção vigente ou etiquetas sem vigência dos últimos 30 dias, opcionalmente filtrados pela origem (rotina diária ou intranet).
     * Para cada loja + produto (ou família) + promoção fica só o PDF mais recente.
     */
    public static function getPdfByStore(Request $request): Collection
    {
        return self::rows(
            RequestGeneratorImage::query()
                ->fromSub(self::filteredByStore($request), 'R')
                ->where('R.RN', 1)
                // Mais novo primeiro
                ->orderByDesc('R.DATA_REQUISICAO')
                ->orderByDesc('R.HORA_REQUISICAO')
                ->orderByDesc('R.REQUISICAO_GERADOR_PLACAS')
                ->select('R.*'),
        );
    }

    /** Linhas do Recuperar PDF numeradas da mais recente para a mais antiga por loja + produto (ou família) + promoção */
    private static function filteredByStore(Request $request): Builder
    {
        $origin = PrintOrigin::tryFrom((string) $request->origem);

        return self::query($request)
            ->whereNotNull('RGP.PATH_PDF')
            ->where(fn (Builder $query) => $query
                ->whereRaw('CAST(GETDATE() AS DATE) BETWEEN C.DATA_INICIAL AND C.DATA_FINAL')
                // Sem vigência: reduzidas sem promoção vinculada e ETIQUETAS_GONDULA (datas nulas)
                ->orWhere(fn (Builder $query) => $query
                    ->whereRaw('(B.ETIQUETA_PLACAS_RESULTADO_ID IS NULL OR C.DATA_INICIAL IS NULL)')
                    ->whereRaw(self::REQUESTED_AT . ' >= ?', [now()->subDays(self::DIAS_SEM_VIGENCIA)->format('Y-m-d\TH:i:s')])))
            ->when(
                $origin === PrintOrigin::COMANDO,
                fn (Builder $query) => $query->where('RGP.ORIGEM_IMPRESSAO', PrintOrigin::COMANDO->value),
            )
            // Requisições anteriores à coluna ORIGEM_IMPRESSAO ficam nulas e vieram da geração padrão
            ->when(
                $origin === PrintOrigin::PADRAO,
                fn (Builder $query) => $query->where(fn (Builder $query) => $query
                    ->where('RGP.ORIGEM_IMPRESSAO', PrintOrigin::PADRAO->value)
                    ->orWhereNull('RGP.ORIGEM_IMPRESSAO')),
            )
            // SQL Server não aceita ORDER BY em subquery; a ordenação vai para a query externa
            ->reorder()
            // Linhas de família vêm com PRODUTO = 0, então a família entra na chave só nesses casos
            ->selectRaw(
                'ROW_NUMBER() OVER (
                    PARTITION BY RGP.LOJA, ISNULL(B.PRODUTO, 0), CASE WHEN ISNULL(B.PRODUTO, 0) = 0 THEN B.FAMILIA END, C.PROCFIT_TIPO
                    ORDER BY RGP.DATA_REQUISICAO DESC, RGP.HORA_REQUISICAO DESC, RGP.REQUISICAO_GERADOR_PLACAS DESC
                ) AS RN',
            );
    }

    /** Produtos sem promoção, sem vigência (ex.: ETIQUETAS_GONDULA vem com datas nulas) ou com promoção vigente hoje */
    public static function vigentes(Builder $query): Builder
    {
        return $query->whereRaw(
            '(B.ETIQUETA_PLACAS_RESULTADO_ID IS NULL OR C.DATA_INICIAL IS NULL OR CAST(GETDATE() AS DATE) BETWEEN C.DATA_INICIAL AND C.DATA_FINAL)',
        );
    }

    /** Requisições com PDF gerado nas últimas 72 horas */
    public static function generatedInLastHours(Builder $query, int $hours = self::HORAS): Builder
    {
        return $query
            ->whereRaw(self::REQUESTED_AT . ' >= ?', [now()->subHours($hours)->format('Y-m-d\TH:i:s')])
            ->whereNotNull('RGP.PATH_PDF');
    }

    /** Requisições da loja com produtos, família e promoção, aplicando os filtros opcionais do request */
    public static function query(Request $request): Builder
    {
        return RequestGeneratorImage::query()
            ->from(self::table(new RequestGeneratorImage, 'RGP'))
            ->join(
                self::table(new RequestGeneratorImageProduct, 'B'),
                'RGP.REQUISICAO_GERADOR_PLACAS',
                '=',
                'B.REQUISICAO_GERADOR_PLACAS',
            )
            ->leftJoin(self::table(new DailyProducts, 'C'), 'C.ID', '=', 'B.ETIQUETA_PLACAS_RESULTADO_ID')
            ->leftJoin(self::table(new Products, 'D'), 'B.PRODUTO', '=', 'D.PRODUTO')
            ->leftJoin(self::table(new FamiliaProduto, 'F'), 'F.FAMILIA_PRODUTO', '=', 'B.FAMILIA')
            ->where('RGP.LOJA', $request->store)
            ->when($request->filled('template'), function ($query) use ($request) {
                $query->where('RGP.TEMPLATE_ID', $request->template);
            })
            ->when($request->filled('produto'), function ($query) use ($request) {
                $query->where('B.PRODUTO', $request->produto);
            })
            ->when($request->filled('familia'), function ($query) use ($request) {
                $query->where('B.FAMILIA', $request->familia);
            })
            ->when($request->filled('promocao'), function ($query) use ($request) {
                $query->where('C.PROCFIT_TIPO', $request->promocao);
            })
            // Mais novo primeiro
            ->orderByDesc('RGP.DATA_REQUISICAO')
            ->orderByDesc('RGP.HORA_REQUISICAO')
            ->orderByDesc('RGP.REQUISICAO_GERADOR_PLACAS')
            ->select([
                'RGP.REQUISICAO_GERADOR_PLACAS',
                'RGP.TEMPLATE_ID',
                'RGP.REQUISICAO',
                'RGP.DATA_REQUISICAO',
                'RGP.HORA_REQUISICAO',
                'RGP.PATH_PDF',
                'RGP.LOJA',
                'B.PRODUTO',
                'D.DESCRICAO_REDUZIDA',
                'B.FAMILIA as FAMILIA_PRODUTO',
                'F.DESCRICAO as FAMILIA_DESCRICAO',
                'C.PROCFIT_TIPO',
                'C.PRECO_PROMOCAO',
            ]);
    }

    /** Executa a query e troca PROCFIT_TIPO/PRECO_PROMOCAO pelo nome da promoção (TypePages::notificacao) */
    public static function rows(Builder $query): Collection
    {
        return $query
            ->get()
            ->map(function ($item) {
                $item->DESCRICAO_PROMOCAO = TypePagesEnum::notificacao($item->PROCFIT_TIPO) ?? 'Etiquetas Reduzidas';

                unset($item->PROCFIT_TIPO, $item->PRECO_PROMOCAO);

                return $item;
            });
    }

    /** Nome completo da tabela do model (BANCO.dbo.TABELA as ALIAS), pois os models estão em bancos diferentes */
    private static function table(Model $model, string $alias): string
    {
        return sprintf(
            '%s.dbo.%s as %s',
            $model->getConnection()->getDatabaseName(),
            $model->getTable(),
            $alias,
        );
    }
}
