<?php

namespace App\Query;

use App\Enums\TypePages as TypePagesEnum;
use App\Models\DailyProducts;
use App\Models\FamiliaProduto;
use App\Models\Products;
use App\Models\RequestGeneratorImage;
use App\Models\RequestGeneratorImageProduct;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PdfByStoreQuery
{
    public static function getPdfByStore(Request $request): Collection
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
            ->whereRaw(
                '(B.ETIQUETA_PLACAS_RESULTADO_ID IS NULL OR CAST(GETDATE() AS DATE) BETWEEN C.DATA_INICIAL AND C.DATA_FINAL)',
            )
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
            ])
            ->get()
            ->map(function ($item) {
                $item->DESCRICAO_PROMOCAO = TypePagesEnum::descricaoPromocao($item->PROCFIT_TIPO, $item->PRECO_PROMOCAO);

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
