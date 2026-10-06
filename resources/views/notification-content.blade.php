<h2>REGISTROS DE IMPRESSÃO DE PLACAS/ETIQUETAS CRIADAS</h2>

<p>Para realizar a impressão das placas ou etiquetas geradas, clique no <b>link disponível em cada registro</b> e faça
    o
    download do arquivo.
</p>

<ul>
    <li>Folhas Brancas: Alterações de Preço</li>
    <li>Folhas Amarelas: Promoção</li>
    <li>Folhas Rosas: PromoClube</li>
</ul>

@include('notification-table', ['registros' => $registros])

@if (! empty($pendentes))
    <h2>REGISTROS NÃO IMPRESSOS NAS ÚLTIMAS 72 HORAS</h2>

    @include('notification-table', ['registros' => $pendentes])
@endif
