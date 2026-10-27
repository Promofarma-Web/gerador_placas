#!/usr/bin/env bash
# Gera as placas das lojas 1, 2, 3 e 5, 2 lojas por vez.
# Argumentos extras vão para o comando, ex: ./scripts/recover-todas-lojas.sh --todos
# A saída também é gravada em storage/logs/products-recover-AAAA-MM-DD.log
set -euo pipefail

CONTAINER="${CONTAINER:-gerador_placas_php}"
LOJAS="${LOJAS:-1,2,3,5}"
PARALELO="${PARALELO:-2}"
LOG="$(cd "$(dirname "$0")/.." && pwd)/storage/logs/products-recover-$(date +%F).log"

INICIO=$(date +%s)
status=0

docker exec "$CONTAINER" php artisan products:recover --loja="$LOJAS" --agrupar --paralelo="$PARALELO" "$@" 2>&1 | tee -a "$LOG" || status=$?

DURACAO=$(( $(date +%s) - INICIO ))

echo
echo "=================================================="
if [ "$status" -eq 0 ]; then
    echo " Concluído com sucesso"
else
    echo " Concluído COM ERROS (código $status) - veja o e-mail de erro e o log"
fi
echo " Lojas:    $LOJAS (de $PARALELO em $PARALELO)"
echo " Término:  $(date '+%d/%m/%Y %H:%M:%S')"
printf ' Duração:  %02dh%02dm%02ds\n' $((DURACAO / 3600)) $((DURACAO % 3600 / 60)) $((DURACAO % 60))
echo " Log:      $LOG"
echo "=================================================="

exit "$status"
