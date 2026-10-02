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

<table style='border-collapse: collapse; width: 100%; font-family: Arial, sans-serif'>
    <thead>
        <tr>
            <th style='border: 1px solid #ddd; padding: 10px; text-align: left'>Tipo de Folha</th>
            <th style='border: 1px solid #ddd; padding: 10px; text-align: left'>Tipo</th>

            <th style='border: 1px solid #ddd; padding: 10px; text-align: left'>PDF</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($registros as $registro)
            @foreach ($registro['paths'] as $path)
                @foreach ($registro['folhas'] as $folha)
                    <tr style="background-color: {{ $folha['cor'] }}">
                        <td style='border: 1px solid #ddd; padding: 10px'>{{ $folha['tipo_folha'] }}</td>
                        <td style='border: 1px solid #ddd; padding: 10px'>{{ $folha['tipo'] }}</td>

                        <td style='border: 1px solid #ddd; padding: 10px'>
                            <a href="{{ url('img/' . $path) }}">Clique aqui para o Download</a>
                        </td>
                    </tr>
                @endforeach
            @endforeach
        @endforeach
    </tbody>
</table>
