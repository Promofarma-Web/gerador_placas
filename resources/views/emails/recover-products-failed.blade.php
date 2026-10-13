<!DOCTYPE html>
<html lang="pt-BR">
<body style="font-family: Arial, sans-serif; color: #222;">
    <h2 style="color: #c0392b;">Erro na geração de placas</h2>

    <p>O comando <strong>products:recover</strong> terminou com erro em {{ now()->format('d/m/Y H:i:s') }}.</p>

    <h3>Opções</h3>
    <ul>
        @foreach ($options as $name => $value)
            <li><strong>--{{ $name }}</strong>: {{ is_bool($value) ? ($value ? 'sim' : 'não') : ($value ?? '-') }}</li>
        @endforeach
    </ul>

    <h3>Erros ({{ count($errors) }})</h3>
    <ol>
        @foreach ($errors as $error)
            <li><pre style="white-space: pre-wrap; margin: 0 0 8px;">{{ $error }}</pre></li>
        @endforeach
    </ol>
</body>
</html>
