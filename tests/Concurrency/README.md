# Prova de concorrência — FIN-SEC-01

```bash
bash tests/Concurrency/prove.sh
```

Sai `0` quando todas as disputas terminam bem e nenhum invariante é violado.

## Por que isto existe fora do PHPUnit

A suíte roda em SQLite, que não tem lock de linha nem MVCC: `lockForUpdate()`
vira no-op, e toda prova de concorrência passa por acidente. Mesmo em MySQL, um
teste de processo único não observa um lado **bloqueado** enquanto o outro
trabalha — quem bloqueia é o processo inteiro.

Disputa de verdade precisa de dois processos e um banco de verdade. Cada
cenário sobe dois processos: um segura o lock por um tempo conhecido, o outro
chega depois e precisa esperar. O tempo de espera impresso é a prova de que o
lock existe.

Achados que só apareceram aqui:

- **FIN-SEC-01F-D** — sob `REPEATABLE READ`, a releitura após violação de
  unicidade lia um snapshot anterior ao commit do vencedor.
- **FIN-SEC-01G** — com oito entregas simultâneas, o perdedor pode chegar como
  *deadlock* (1213), e não como chave duplicada (1062).

## Segurança

Trabalha sempre num banco **descartável** (`fel_scratch_finsec01g` por padrão),
criado no início e derrubado no fim. O banco de desenvolvimento nunca é tocado:
sem `migrate:fresh`, sem `db:wipe`, sem `ANALYZE TABLE`.

## Ajustes

| Variável | Padrão | Serve para |
|---|---|---|
| `FEL_APP_CONTAINER` | `fel_app` | container que roda o PHP |
| `FEL_MYSQL_CONTAINER` | `fel_mysql` | container do MySQL |
| `FEL_SCRATCH_DB` | `fel_scratch_finsec01g` | banco descartável |
| `FEL_SEGURA_MS` | `2000` | quanto o primeiro lado segura o lock |
| `FEL_ATRASO_MS` | `300` | quando o segundo lado chega |

Em máquina lenta, aumentar `FEL_SEGURA_MS`: se o primeiro lado soltar o lock
antes de o segundo chegar, o cenário roda em série e deixa de provar disputa.

## Cenários

| Disputa | O que precisa acontecer |
|---|---|
| checkout × checkout | a última peça vai para um só; o outro não fabrica estoque |
| checkout × alteração de estoque | o lojista não zera por baixo de uma reserva viva |
| checkout × exclusão da oferta | oferta com reserva ativa recusa exclusão |
| pagamento × pagamento | uma confirmação, um `paid_at`, um consumo de estoque |
| pagamento × cancelamento | quem chega depois relê o estado e recua |
| pagamento × expiração | estoque consumido nunca volta para a prateleira |
| expiração × expiração | uma liberação só |
| refund × refund | uma reversão, um `reversed_at`, um `reverted_at` |
| refund × confirmação | `Estornado` não ressuscita |
| confirmação × refund | a reversão enxerga a confirmação inteira, nunca metade |
| approved tardio × Estornado | recusado; o dinheiro vira reconciliação, não venda |
| PaymentConflict duplicado | oito entregas do mesmo evento, uma linha |

Ao final, os invariantes são conferidos sobre tudo que ficou no banco —
inclusive `split confirmado em pedido encerrado`, que é o achado G-1.
