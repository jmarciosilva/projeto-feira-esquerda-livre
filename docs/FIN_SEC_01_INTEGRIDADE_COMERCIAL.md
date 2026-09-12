# FIN-SEC-01 — ponteiro

> **Este arquivo não contém mais documentação.** Ele existe apenas porque
> `tests/Concurrency/prove.sh` ainda o cita no cabeçalho, e a consolidação
> documental DOC-CONSOLIDATION-01 não altera código nem testes.

O conteúdo da FIN-SEC-01 foi consolidado em:

- **Arquitetura, invariantes e decisões D-FIN-01 a D-FIN-45** —
  [`docs/ARCHITECTURE.md`](ARCHITECTURE.md), seções *Checkout e pedidos*,
  *Pagamentos*, *Estoque* e *Decision Log*.
- **Prova de concorrência em MySQL real** (`tests/Concurrency/prove.sh`) — como
  rodar em [`README.md`](../README.md), seção *Testes*; por que existe em
  [`docs/ARCHITECTURE.md`](ARCHITECTURE.md), seção *Testes e validação*.
- **Estado das subfases e dívidas remanescentes** — [`ROADMAP.md`](../ROADMAP.md).

O texto original permanece no histórico do Git (último commit com o documento
completo: `6ae5981`).

Quando o comentário de `prove.sh` for atualizado para apontar para
`docs/ARCHITECTURE.md`, este arquivo pode ser removido.
