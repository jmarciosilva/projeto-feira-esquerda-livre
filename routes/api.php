<?php

use App\Http\Controllers\Api\V1\AgendaController;
use App\Http\Controllers\Api\V1\AprendizadoController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CarrinhoController;
use App\Http\Controllers\Api\V1\CatalogoController;
use App\Http\Controllers\Api\V1\CategoriaController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\ContatoController;
use App\Http\Controllers\Api\V1\EnderecoController;
use App\Http\Controllers\Api\V1\ExpositorSolicitacaoController;
use App\Http\Controllers\Api\V1\FeedController;
use App\Http\Controllers\Api\V1\LojaController;
use App\Http\Controllers\Api\V1\NoticiaController;
use App\Http\Controllers\Api\V1\Lojista\CursoController as LojistaCursoController;
use App\Http\Controllers\Api\V1\Lojista\ExposicaoController as LojistaExposicaoController;
use App\Http\Controllers\Api\V1\Lojista\LojaController as LojistaLojaController;
use App\Http\Controllers\Api\V1\Lojista\PainelController as LojistaPainelController;
use App\Http\Controllers\Api\V1\Lojista\PedidoController as LojistaPedidoController;
use App\Http\Controllers\Api\V1\Lojista\PerguntaController as LojistaPerguntaController;
use App\Http\Controllers\Api\V1\Lojista\ProdutoController as LojistaProdutoController;
use App\Http\Controllers\Api\V1\PedidoController;
use App\Http\Controllers\Api\V1\PedidoMensagemController;
use App\Http\Controllers\Api\V1\ProductQuestionController;
use App\Http\Controllers\Api\V1\RastreioController;
use App\Http\Controllers\ShippingController;
use Illuminate\Support\Facades\Route;

// API REST consumida pelo app mobile (Flutter). Documentação completa em docs/API.md.
Route::prefix('v1')->name('api.v1.')->group(function () {

    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/registrar', [AuthController::class, 'register'])->name('register');
        Route::post('/entrar', [AuthController::class, 'login'])->name('login');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/sair', [AuthController::class, 'logout'])->name('logout');
            Route::get('/eu', [AuthController::class, 'me'])->name('me');
        });
    });

    // ─── Catálogo público por eixo ────────────────────────────────────────
    foreach (['produtos' => 'produto', 'servicos' => 'servico', 'cuidados' => 'cuidado'] as $slug => $tipo) {
        Route::get("/{$slug}", fn (\Illuminate\Http\Request $request) => (new CatalogoController)->index($request, $tipo))
            ->name("catalogo.{$tipo}");
    }

    Route::get('/produtos/{product}', [CatalogoController::class, 'show'])->name('produtos.show');
    Route::get('/produtos/{product}/perguntas', [ProductQuestionController::class, 'index'])->name('produtos.perguntas.index');

    Route::get('/lojas', [LojaController::class, 'index'])->name('lojas.index');
    Route::get('/lojas/{slug}', [LojaController::class, 'show'])->name('lojas.show');
    Route::get('/categorias', [CategoriaController::class, 'index'])->name('categorias.index');
    Route::get('/agenda', [AgendaController::class, 'index'])->name('agenda.index');
    Route::get('/agenda/{slug}', [AgendaController::class, 'show'])->name('agenda.show');
    Route::get('/rastreio/{trackingCode}', [RastreioController::class, 'show'])->name('rastreio.show');
    Route::get('/noticias', [NoticiaController::class, 'index'])->name('noticias.index');
    Route::get('/noticias/{slug}', [NoticiaController::class, 'show'])->name('noticias.show');
    Route::get('/contato', [ContatoController::class, 'show'])->name('contato.show');

    // Mesmos fluxos dos formulários públicos do site (contato e seja-um-
    // expositor), só que sem sair do app — ver routes/web.php para o
    // equivalente com view/redirect usado pelo navegador.
    Route::post('/contato', [ContatoController::class, 'store'])->name('contato.store');
    Route::post('/seja-um-expositor', [ExpositorSolicitacaoController::class, 'store'])->name('seja-um-expositor.store');

    // Reaproveita o mesmo controller já usado pelo checkout web — sem duplicar lógica.
    Route::post('/frete/cotacao', [ShippingController::class, 'quote'])->name('frete.cotacao');

    // ─── Comunidade (feed) ──────────────────────────────────────────────
    // Leitura é pública, igual ao site; curtir/comentar/denunciar exigem login.
    Route::get('/feed', [FeedController::class, 'index'])->name('feed.index');
    Route::get('/feed/{post}/comentarios', [FeedController::class, 'comentarios'])->name('feed.comentarios.index');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/produtos/{product}/perguntas', [ProductQuestionController::class, 'store'])->name('produtos.perguntas.store');

        Route::post('/feed/{post}/curtir', [FeedController::class, 'curtir'])->name('feed.curtir');
        Route::post('/feed/{post}/comentarios', [FeedController::class, 'comentar'])->name('feed.comentarios.store');
        Route::post('/feed/{post}/denunciar', [FeedController::class, 'denunciar'])->name('feed.denunciar');

        // ─── Carrinho ───────────────────────────────────────────────────
        Route::get('/carrinho', [CarrinhoController::class, 'index'])->name('carrinho.index');
        Route::post('/carrinho/itens', [CarrinhoController::class, 'store'])->name('carrinho.itens.store');
        Route::patch('/carrinho/itens/{item}', [CarrinhoController::class, 'update'])->name('carrinho.itens.update');
        Route::delete('/carrinho/itens/{item}', [CarrinhoController::class, 'destroy'])->name('carrinho.itens.destroy');

        // ─── Checkout e pedidos ─────────────────────────────────────────
        Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
        Route::get('/pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
        Route::get('/pedidos/{reference}', [PedidoController::class, 'show'])->name('pedidos.show');
        Route::get('/pedidos/{reference}/pagar', [PedidoController::class, 'pagar'])->name('pedidos.pagar');

        Route::get('/pedidos/splits/{split}/mensagens', [PedidoMensagemController::class, 'index'])->name('pedidos.mensagens.index');
        Route::post('/pedidos/splits/{split}/mensagens', [PedidoMensagemController::class, 'store'])->name('pedidos.mensagens.store');

        // ─── Endereços ──────────────────────────────────────────────────
        Route::get('/enderecos', [EnderecoController::class, 'index'])->name('enderecos.index');
        Route::post('/enderecos', [EnderecoController::class, 'store'])->name('enderecos.store');
        Route::put('/enderecos/{endereco}', [EnderecoController::class, 'update'])->name('enderecos.update');
        Route::delete('/enderecos/{endereco}', [EnderecoController::class, 'destroy'])->name('enderecos.destroy');

        // ─── AVA — Meu Aprendizado ────────────────────────────────────────
        Route::get('/aprendizado', [AprendizadoController::class, 'index'])->name('aprendizado.index');
        Route::get('/aprendizado/{enrollment}', [AprendizadoController::class, 'show'])->name('aprendizado.show');
        Route::post('/aprendizado/{enrollment}/aulas/{lesson}/concluir', [AprendizadoController::class, 'concluirAula'])->name('aprendizado.aulas.concluir');
        Route::get('/aprendizado/{enrollment}/certificado', [AprendizadoController::class, 'certificado'])->name('aprendizado.certificado');
    });

    // ─── Lojista ────────────────────────────────────────────────────────────
    Route::prefix('lojista')->name('lojista.')->middleware(['auth:sanctum', 'lojista'])->group(function () {
        Route::get('/painel', [LojistaPainelController::class, 'index'])->name('painel');

        Route::get('/loja', [LojistaLojaController::class, 'show'])->name('loja.show');
        Route::put('/loja', [LojistaLojaController::class, 'update'])->name('loja.update');

        Route::get('/produtos', [LojistaProdutoController::class, 'index'])->name('produtos.index');
        Route::post('/produtos', [LojistaProdutoController::class, 'store'])->name('produtos.store');
        Route::get('/produtos/{product}', [LojistaProdutoController::class, 'show'])->name('produtos.show');
        Route::put('/produtos/{product}', [LojistaProdutoController::class, 'update'])->name('produtos.update');
        Route::delete('/produtos/{product}', [LojistaProdutoController::class, 'destroy'])->name('produtos.destroy');

        Route::get('/pedidos', [LojistaPedidoController::class, 'index'])->name('pedidos.index');
        Route::patch('/pedidos/{split}/confirmar-pagamento', [LojistaPedidoController::class, 'confirmarPagamento'])->name('pedidos.confirmar-pagamento');
        Route::patch('/pedidos/{split}/marcar-enviado', [LojistaPedidoController::class, 'marcarEnviado'])->name('pedidos.marcar-enviado');

        Route::get('/perguntas', [LojistaPerguntaController::class, 'index'])->name('perguntas.index');
        Route::patch('/perguntas/{question}/responder', [LojistaPerguntaController::class, 'responder'])->name('perguntas.responder');
        Route::patch('/perguntas/{question}/visibilidade', [LojistaPerguntaController::class, 'visibilidade'])->name('perguntas.visibilidade');

        Route::get('/exposicao', [LojistaExposicaoController::class, 'show'])->name('exposicao.show');

        Route::get('/cursos', [LojistaCursoController::class, 'index'])->name('cursos.index');
        Route::patch('/cursos/{course}/publicar', [LojistaCursoController::class, 'publicar'])->name('cursos.publicar');
    });
});
