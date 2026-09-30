<p>Bom dia, loja {{ $logger->LOJA }}.</br>Devido à precificação, alguns produtos tiveram mudanças em seus preços.
    Abaixo você pode
    conferir as etiquetas criadas, separadas por template. Obrigado pela sua atenção.</p>

<table style='border-collapse: collapse; width: 100%; font-family: Arial, sans-serif;'>
    <thead>
        <tr>
            <th style='border: 1px solid #ddd; padding: 10px; text-align: left;'>Tipo da Folha</th>
            <th style='border: 1px solid #ddd; padding: 10px; text-align: left;'>Título</th>
            <th style='border: 1px solid #ddd; padding: 10px; text-align: left;'>PDF</th>
        </tr>
    </thead>
    <tbody>

        @foreach ($paths as $url)
            {{-- $titulo = $templates[$item['template_id']]->TITULO ?? "Template {$item['template_id']}"; --}}
            {{-- $label = ColorRules::getLabel($item['type'], $item['template_id']); --}}
            <tr>
                <td style='border: 1px solid #ddd; padding: 10px; color: {$label['color']}; background-color:
                    {$label['background-color']}'>Folha A4 Picotada - {$label['title']}</td>
                <td style='border: 1px solid #ddd; padding: 10px;'>{$titulo}</td>
                <td style='border: 1px solid #ddd; padding: 10px;'>
                    <a href={{ $url }}}' target='_blank'>Clique para abrir PDF</a>
                </td>
            </tr>";
        @endforeach

    </tbody>
</table>
