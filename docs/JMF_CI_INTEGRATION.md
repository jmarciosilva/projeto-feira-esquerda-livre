# Integração JMF Customer Intelligence — Feira Esquerda Livre

**Versão:** 1.1  
**Data:** 8 de agosto de 2026  
**Status:** Concluído (Sprint 3 — Fase 10 completa: dashboard, rastreamento e testes E2E)

---

## 📋 Sumário

1. [Instalação e Configuração](#-instalação-e-configuração)
2. [Componentes Livewire](#-componentes-livewire)
3. [Rastreamento de Eventos](#-rastreamento-de-eventos)
4. [Dashboard Admin](#-dashboard-admin)
5. [Testes Automatizados](#-testes-automatizados)
6. [Troubleshooting](#-troubleshooting)

---

## 🚀 Instalação e Configuração

### Pré-requisitos

- Laravel 12+
- PHP 8.2+
- Composer
- Acesso à plataforma JMF CI (http://179.198.115.221)

### Passos de Instalação

#### 1. Instalar o SDK

```bash
# Clonar repositório (se não feito)
cd /caminho/do/projeto
git clone https://github.com/jmarciosilva/projeto_JMFCustomerIntelligencePlatform.git jmf-ci-sdk

# Adicionar ao composer.json (já configurado)
composer require jmf-system/customer-intelligence-sdk:^1.0.0
```

#### 2. Configurar Variáveis de Ambiente

Adicione ao arquivo `.env`:

```env
# JMF Customer Intelligence SDK
JMF_CI_BASE_URL=http://179.198.115.221
JMF_CI_TOKEN=<token-gerado-no-painel-admin>
JMF_CI_QUEUE_CONNECTION=sync
JMF_CI_TIMEOUT=2
JMF_CI_SYNC=false
JMF_CI_VALIDATE_ON_BOOT=true
```

#### 3. Gerar Token de API

1. Acesse http://179.198.115.221/admin/login
2. Faça login com:
   - Email: `admin-homolog@jmfsystem.tech`
   - Senha: `JesusCristo@2026`
3. Navegue para: **Tenants > Applications**
4. Crie uma nova aplicação "Feira Esquerda Livre"
5. Gere um token e copie para `JMF_CI_TOKEN` no `.env`

#### 4. Publicar Configuração

```bash
php artisan vendor:publish --provider="JmfSystem\CustomerIntelligence\CustomerIntelligenceServiceProvider"
```

Isso cria: `config/customer-intelligence.php`

#### 5. Testar Conexão

```bash
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
\$config = config('customer-intelligence');
echo '✓ Configuração: ' . \$config['base_url'] . '\n';
"
```

---

## 🎨 Componentes Livewire

### 1. Dashboard

Exibe métricas de inteligência de cliente em tempo real.

```blade
<livewire:jmf-ci-dashboard />
```

**Funcionalidades:**
- Resumo de visitas, conversões e eventos
- Gráficos de comportamento
- Período selecionável

### 2. Configuração

Valida conexão com API JMF CI.

```blade
<livewire:jmf-ci-configuration />
```

**Funcionalidades:**
- Testa conectividade com a API
- Exibe status de sincronização
- Mostra última sincronização

### 3. Lista de Contatos

Exibe contatos rastreados e suas interações.

```blade
<livewire:jmf-ci-contact-index />
```

**Funcionalidades:**
- Listagem paginada de contatos
- Busca por nome/email
- Filtros por período

### 4. Detalhe de Contato

Informações completas de um contato específico.

```blade
<livewire:jmf-ci-contact-show :contact-id="$contactId" />
```

**Funcionalidades:**
- Histórico de interações
- Timeline de eventos
- Informações de conversão

### 5. Tabela de Eventos

Visualiza todos os eventos rastreados.

```blade
<livewire:jmf-ci-event-index />
```

**Funcionalidades:**
- Filtros por tipo de evento
- Ordenação por data
- Busca por ID do visitante

---

## 📊 Rastreamento de Eventos

### Usando a Facade

```php
use JmfSystem\CustomerIntelligence\Facades\CustomerIntelligence;

CustomerIntelligence::track('evento.nome', [
    'chave1' => 'valor1',
    'chave2' => 'valor2',
]);
```

### Eventos Implementados

#### Produtos

**`produto.visualizado`**
```php
// ProductController::show() ou equivalente
CustomerIntelligence::track('produto.visualizado', [
    'produto_id' => $produto->id,
    'nome' => $produto->nome,
    'preco' => $produto->preco,
    'eixo' => $produto->item_type,      // 'produto', 'servico', 'cuidado'
    'expositor_id' => $produto->expositor_id,
]);
```

**Localização no código:**
- `app/Http/Controllers/ProductController.php` → método `show()`
- `app/Livewire/ProdutoLoja.php` → mount ou render

---

#### Carrinho

**`produto.adicionado_carrinho`**
```php
// CartService::addItem() ou CartDrawer component
CustomerIntelligence::track('produto.adicionado_carrinho', [
    'produto_id' => $product->id,
    'quantidade' => $quantity,
    'preco_unitario' => $product->preco,
    'total_carrinho' => $cart->total(),
]);
```

**`produto.removido_carrinho`**
```php
CustomerIntelligence::track('produto.removido_carrinho', [
    'produto_id' => $cartItem->product_id,
    'quantidade' => $cartItem->quantity,
]);
```

**`carrinho.visualizado`**
```php
CustomerIntelligence::track('carrinho.visualizado', [
    'total_itens' => $cart->count(),
    'valor_total' => $cart->total(),
]);
```

**Localização no código:**
- `app/Services/CartService.php` → `addItem()`, `removeItem()`
- `app/Livewire/CartDrawer.php` → mount

---

#### Pedidos

**`carrinho.checkout_iniciado`**
```php
// CheckoutController::confirmar()
CustomerIntelligence::track('carrinho.checkout_iniciado', [
    'total_itens' => $order->items->count(),
    'valor_total' => $order->grand_total,
    'quantidade_lojas' => $order->splits->count(),
]);
```

**`pedido.criado`**
```php
// OrderService::createFromCart()
CustomerIntelligence::track('pedido.criado', [
    'pedido_id' => $order->id,
    'referencia' => $order->reference,
    'valor_total' => $order->grand_total,
    'quantidade_itens' => $order->items->count(),
    'status_pagamento' => $order->status,
]);
```

**`pedido.pagamento_confirmado`**
```php
// Event: OrderSplitConfirmed
CustomerIntelligence::track('pedido.pagamento_confirmado', [
    'pedido_id' => $order->id,
    'split_id' => $split->id,
    'valor_recebido' => $split->gross_amount,
    'comissao' => $split->commission_amount,
]);
```

**`pedido.enviado`**
```php
// LojistaPedidoController::marcarEnviado()
CustomerIntelligence::track('pedido.enviado', [
    'pedido_id' => $order->id,
    'split_id' => $split->id,
    'transportadora' => $shipping->carrier,
    'codigo_rastreio' => $shipping->tracking_code,
    'prazo_dias' => $shipping->estimated_days,
]);
```

**Localização no código:**
- `app/Http/Controllers/CheckoutController.php` → `confirmar()`
- `app/Services/OrderService.php` → `createFromCart()`
- `app/Listeners/HandleAvaEnrollmentOnSplitConfirmed.php` (event listener)
- `app/Livewire/LojistaPedidoIndex.php` → ação `marcarEnviado()`

---

### Configurando Rastreamento Customizado

Para adicionar rastreamento de eventos customizados:

```php
use JmfSystem\CustomerIntelligence\Facades\CustomerIntelligence;

class SeuModel extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            CustomerIntelligence::track('seu_modelo.criado', [
                'id' => $model->id,
                'usuario_id' => auth()->id(),
            ]);
        });
    }
}
```

---

## 🎯 Dashboard Admin

### Acessar o Dashboard

1. Faça login como admin em `http://localhost:8000/admin`
2. Navegue para **Inteligência de Cliente**
3. Veja:
   - Resumo de métricas (visitas, conversões)
   - Gráficos de comportamento
   - Status de sincronização com API JMF CI

### Permissões

```php
// No painel admin, apenas usuários com role 'admin' podem acessar
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/customer-intelligence', CustomerIntelligenceController::class)
        ->name('admin.customer-intelligence');
});
```

### Validar Conexão

Na página do dashboard, clique em "Validar Conexão" para:
- Testar conectividade com API JMF CI
- Verificar credenciais
- Confirmar que events estão sendo sincronizados

---

## 🧪 Testes Automatizados

### Teste Manual

1. Abra o navegador e vá para um produto: `http://localhost:8000/loja/{slug}/{produto-slug}`
2. Adicione produto ao carrinho
3. Finalize a compra
4. Acesse o dashboard em `http://localhost:8000/admin/customer-intelligence`
5. Verifique se os eventos aparecem (pode levar alguns segundos, dependendo da fila configurada)

### Suíte Automatizada

`tests/Feature/CustomerIntelligence/DashboardTest.php` — acesso ao dashboard por papel (admin, gerente, editor sem permissão, cliente, visitante).

`tests/Feature/CustomerIntelligence/EventTrackingTest.php` — cobre os 7 eventos rastreados (produto visualizado, adicionado/removido do carrinho, checkout iniciado, pedido criado, pagamento confirmado, pedido enviado), usando `Bus::fake()` para interceptar o `SendPayloadJob` sem fazer chamadas HTTP reais:

```php
use Illuminate\Support\Facades\Bus;
use JmfSystem\CustomerIntelligence\Jobs\SendPayloadJob;

Bus::fake();

app(CartService::class)->add($product, 2);

Bus::assertDispatched(SendPayloadJob::class, function (SendPayloadJob $job) use ($product) {
    return $job->endpoint === 'events'
        && $job->payload['event_name'] === 'produto.adicionado_carrinho'
        && $job->payload['properties']['produto_id'] === $product->id;
});
```

Um teste específico (`test_tracking_failure_does_not_break_add_to_cart`) verifica a resiliência de ponta a ponta **sem** `Bus::fake()`: com `QUEUE_CONNECTION=sync` (mesmo valor usado em dev), o Job roda de verdade, mas a chamada HTTP é substituída por uma falha simulada via `Http::fake(['*' => Http::response('Service Unavailable', 500)])`. Isso confirma que o `try/catch` em cada ponto de rastreamento realmente protege o fluxo de negócio mesmo quando a API JMF CI está fora do ar — não apenas em teoria.

Rodar apenas esses testes:

```bash
php artisan test --filter=CustomerIntelligence
```

---

## 🐛 Troubleshooting

### Erro: "Configuração inválida do SDK"

**Causa:** Token ou base_url não configurados

**Solução:**
```bash
# Verificar .env
cat .env | grep JMF_CI

# Se vazio, adicione e execute
php artisan config:clear
```

### Erro: "Connection refused"

**Causa:** API JMF CI indisponível

**Solução:**
1. Verifique se http://179.198.115.221 está acessível
2. Teste: `curl http://179.198.115.221`
3. Se offline, sincronização será enfileirada e processada quando retornar

### Eventos não aparecem no Dashboard

**Causa:** Rastreamento síncrono vs. assíncrono

**Verificar:**
```env
# Para desenvolvimento/teste (modo síncrono):
JMF_CI_QUEUE_CONNECTION=sync

# Para produção (modo assíncrono com fila):
JMF_CI_QUEUE_CONNECTION=database
```

**Debug:**
```bash
# Ver logs de sincronização
tail -f storage/logs/laravel.log | grep "JMF"
```

---

## 📚 Referências

- [JMF CI SDK README](../packages/jmf-system/customer-intelligence-sdk/README.md)
- [Documentação Principal](README.md)
- [Roadmap - Fase 10](ROADMAP.md#-fase-10--inteligência-de-cliente-jmf-ci-em-andamento)

---

*Documento criado em: 8 de agosto de 2026*  
*Próxima atualização: após conclusão do Módulo 10.3 (Testes E2E)*
