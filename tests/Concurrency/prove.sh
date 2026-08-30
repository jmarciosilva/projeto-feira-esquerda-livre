#!/usr/bin/env bash
#
# FIN-SEC-01G — prova de concorrência da trilha FIN-SEC-01, em MySQL real.
#
# Sobe um banco DESCARTÁVEL, roda cada disputa em dois processos paralelos,
# confere os invariantes sobre o que ficou no banco, e derruba o banco no fim.
#
#   bash tests/Concurrency/prove.sh
#
# O banco de desenvolvimento nunca é tocado: nem migrate:fresh, nem db:wipe,
# nem ANALYZE TABLE. Ver docs/FIN_SEC_01_INTEGRIDADE_COMERCIAL.md.
#
set -uo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
APP="${FEL_APP_CONTAINER:-fel_app}"
MYSQL="${FEL_MYSQL_CONTAINER:-fel_mysql}"
BANCO="${FEL_SCRATCH_DB:-fel_scratch_finsec01g}"
HARNESS=/var/www/html/tests/Concurrency/harness.php

# O lado que "segura" mantém o lock por SEGURA_MS; o outro chega ATRASO_MS
# depois e precisa esperar. Tempos folgados de propósito: a prova é o bloqueio,
# não a velocidade.
SEGURA_MS="${FEL_SEGURA_MS:-2000}"
ATRASO_MS="${FEL_ATRASO_MS:-300}"

ROOTPW="$(grep '^DB_ROOT_PASSWORD=' "$RAIZ/.env" | cut -d= -f2-)"
USUARIO="$(grep '^DB_USERNAME=' "$RAIZ/.env" | cut -d= -f2-)"

falhas=0

mysql_root() { docker exec "$MYSQL" mysql -uroot -p"$ROOTPW" "$@" 2>&1 | grep -v 'Using a password\|World-writable'; }
app()        { MSYS_NO_PATHCONV=1 docker exec -e DB_DATABASE="$BANCO" "$APP" "$@"; }
harness()    { app php "$HARNESS" "$@"; }

titulo() { printf '\n\033[1m── %s\033[0m\n' "$1"; }

# Uma disputa: semeia, dispara os dois lados em paralelo, mostra o estado final.
disputa() {
  local nome="$1" cenario="$2" ref="$3" acao_a="$4" acao_b="$5" estoque="${6:-10}" qtd="${7:-2}"

  titulo "$nome"
  harness limpar >/dev/null
  harness seed "$cenario" "$ref" "$estoque" "$qtd" >/dev/null

  app sh -c "php $HARNESS worker $acao_a $ref 0 $SEGURA_MS & php $HARNESS worker $acao_b $ref $ATRASO_MS 0 & wait"
  harness estado "$ref"
}

titulo "banco descartável: $BANCO"
mysql_root -e "DROP DATABASE IF EXISTS \`$BANCO\`;
               CREATE DATABASE \`$BANCO\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
               GRANT ALL ON \`$BANCO\`.* TO '$USUARIO'@'%'; FLUSH PRIVILEGES;"
app php artisan migrate --force --no-interaction | tail -2

# ── Estoque ───────────────────────────────────────────────────────────────────
disputa "checkout × checkout — última peça"        pendente CC01 reservar reservar 2 2
disputa "checkout × alteração de estoque"          pendente CC02 reservar baixar-estoque 5 2
disputa "checkout × exclusão da oferta"            pendente CC03 reservar excluir-oferta 5 2

# ── Pagamento ─────────────────────────────────────────────────────────────────
disputa "pagamento × pagamento"                    pendente PG01 confirmar confirmar 10 2
disputa "pagamento × cancelamento"                 pendente PG02 confirmar cancelar 10 2
disputa "pagamento × expiração"                    pendente PG03 confirmar expirar 10 2
disputa "expiração × expiração"                    pendente PG04 expirar expirar 10 2

# ── Reversão ──────────────────────────────────────────────────────────────────
disputa "refund × refund"                          pago RV01 reverter reverter 10 2
disputa "refund × confirmação"                     pago RV02 reverter confirmar 10 2
disputa "confirmação × refund"                     pendente RV03 confirmar reverter 10 2
disputa "approved tardio × Estornado"              pago RV04 reverter confirmar 10 2

# ── Conflitos ─────────────────────────────────────────────────────────────────
titulo "PaymentConflict duplicado — 8 entregas simultâneas"
harness limpar >/dev/null
harness seed pago CF01 10 2 >/dev/null
app sh -c "for i in 1 2 3 4 5 6 7 8; do php $HARNESS worker conflito CF01 0 0 & done; wait"
harness estado CF01

# ── Invariantes sobre tudo que ficou ──────────────────────────────────────────
titulo "invariantes"
if ! harness invariantes; then
  falhas=1
fi

titulo "limpeza"
mysql_root -e "DROP DATABASE \`$BANCO\`;"
echo "  banco descartável removido; banco de desenvolvimento intacto"

if [ "$falhas" -ne 0 ]; then
  printf '\n\033[31mFIN-SEC-01G — INVARIANTE VIOLADO\033[0m\n'
  exit 1
fi

printf '\n\033[32mFIN-SEC-01G — concorrência provada, invariantes intactos\033[0m\n'
