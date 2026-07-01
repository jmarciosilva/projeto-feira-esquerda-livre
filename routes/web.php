<?php

use App\Enums\ItemType;
use App\Enums\MenuLocation;
use App\Http\Controllers\MercadoPagoPaymentController;
use App\Http\Controllers\ProductShareImageController;
use App\Http\Controllers\ProductSharePreviewController;
use App\Http\Controllers\ShippingController;
use App\Livewire\Admin\Banners\BannerForm;
use App\Livewire\Admin\Banners\BannerIndex;
use App\Livewire\Admin\Categorias\CategoriaForm;
use App\Livewire\Admin\Categorias\CategoriaIndex;
use App\Livewire\Admin\Clientes\ClienteIndex;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\EmailMarketing\CampaignForm as CampaignFormLivewire;
use App\Livewire\Admin\EmailMarketing\CampaignIndex as CampaignIndexLivewire;
use App\Livewire\Admin\EmailMarketing\CampaignReport;
use App\Livewire\Admin\Events\EventForm;
use App\Livewire\Admin\Events\EventIndex;
use App\Livewire\Admin\Expositores\ExpositoresForm;
use App\Livewire\Admin\Expositores\ExpositoresIndex;
use App\Livewire\Admin\Expositores\VisibilityIndex as ExpositoresVisibilityIndex;
use App\Livewire\Admin\Feed\ReportIndex as FeedReportIndex;
use App\Livewire\Admin\Lojistas\SolicitacaoIndex;
use App\Livewire\Admin\Media\MediaLibrary;
use App\Livewire\Admin\Menus\MenuForm;
use App\Livewire\Admin\Menus\MenuIndex;
use App\Livewire\Admin\Pages\PageForm;
use App\Livewire\Admin\Pages\PageIndex;
use App\Livewire\Admin\Pedidos\PedidoIndex as AdminPedidoIndex;
use App\Livewire\Admin\Permissoes\PerfilAcessoIndex;
use App\Livewire\Admin\Posts\PostForm;
use App\Livewire\Admin\Posts\PostIndex;
use App\Livewire\Admin\Settings\CheckoutSettingsForm;
use App\Livewire\Admin\Settings\MailSettingsForm;
use App\Livewire\Admin\Settings\SettingsForm;
use App\Livewire\Admin\Usuarios\UsuarioForm;
use App\Livewire\Admin\Usuarios\UsuarioIndex;
use App\Livewire\Cliente\Enderecos\EnderecoForm;
use App\Livewire\Cliente\Enderecos\EnderecoIndex;
use App\Livewire\Cliente\Pedidos\PedidoIndex as ClientePedidoIndex;
use App\Livewire\Lojista\Dashboard as LojistaDashboard;
use App\Livewire\Lojista\ExposicaoIndex as LojistaExposicaoIndex;
use App\Livewire\Lojista\Feed\FeedPostIndex;
use App\Livewire\Lojista\LojaForm;
use App\Livewire\Lojista\Pedidos\PedidoIndex as LojistaPedidoIndex;
use App\Livewire\Lojista\Produtos\ProdutoForm;
use App\Livewire\Lojista\Produtos\ProdutoIndex;
use App\Mail\LojistaSolicitacaoRecebida;
use App\Models\Banner;
use App\Models\ContentCategory;
use App\Models\Event;
use App\Models\Expositor;
use App\Models\LojistasSolicitacao;
use App\Models\Menu;
use App\Models\NewsletterSubscriber;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignSend;
use App\Models\Order;
use App\Models\OrderShipping;
use App\Services\ExpositorVisibilityService;
use App\Models\Post;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

// ─── Homepage pública ────────────────────────────────────────────────────────
Route::get('/', function () {
    $settings = SiteSetting::instance();
    $banners = Banner::visible()->get();
    $headerMenu = Menu::where('location', MenuLocation::Header)
        ->where('is_active', true)
        ->with(['items' => fn ($q) => $q->where('is_active', true)
            ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order'),
        ])
        ->first();

    $upcomingEvents = Event::upcoming()->limit(3)->get();

    $sessionHash = hash('sha256', session()->getId());
    $featuredExpositores = app(ExpositorVisibilityService::class)->selectForHome($sessionHash);
    $featuredProducts = Product::featured()->where('item_type', 'produto')->with('expositor')->limit(10)->get();
    $featuredServicos = Product::featured()->where('item_type', 'servico')->with('expositor')->limit(10)->get();
    $featuredCuidados = Product::featured()->where('item_type', 'cuidado')->with('expositor')->limit(10)->get();
    $latestPosts = Post::published()->orderByDesc('published_at')->limit(5)->get();

    return view('welcome', compact(
        'settings', 'banners', 'headerMenu',
        'upcomingEvents', 'featuredExpositores', 'featuredProducts', 'featuredServicos', 'featuredCuidados', 'latestPosts',
    ));
});

// ─── Newsletter ───────────────────────────────────────────────────────────────
Route::post('/newsletter', function (Request $request) {
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
    ]);

    NewsletterSubscriber::updateOrCreate(
        ['email' => $validated['email']],
        ['name' => $validated['name'], 'is_active' => true]
    );

    return back()->with('newsletter_success', 'Obrigado! Você foi inscrito com sucesso.');
})->name('newsletter.subscribe');

// ─── Catálogo público por eixo ────────────────────────────────────────────────
foreach (['produtos' => 'produto', 'servicos' => 'servico', 'cuidados' => 'cuidado'] as $slug => $tipo) {
    Route::get("/{$slug}", function (Request $request) use ($tipo) {
        $eixo = ItemType::from($tipo);
        $busca = $request->input('busca', '');
        $categoriaId = (int) $request->input('categoria', 0);

        $items = Product::where('item_type', $tipo)
            ->where('is_active', true)
            ->with('expositor')
            ->when($busca, fn ($q) => $q->where('name', 'like', "%{$busca}%"))
            ->when($categoriaId, fn ($q) => $q->where('category_id', $categoriaId))
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(24);

        $categorias = ContentCategory::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('eixo')->orWhere('eixo', $tipo))
            ->orderBy('name')
            ->get();

        return view('catalogo.index', compact('eixo', 'items', 'categorias'));
    })->name("catalogo.{$tipo}");
}

// ─── Seja um Expositor (público) ─────────────────────────────────────────────
Route::get('/seja-um-expositor', function () {
    return view('seja-um-expositor');
})->name('seja-um-expositor');

Route::post('/seja-um-expositor', function (Request $request) {
    $validated = $request->validate([
        'nome_loja' => 'required|string|max:255',
        'responsavel' => 'required|string|max:255',
        'cpf_cnpj' => 'required|string|max:20',
        'whatsapp' => 'required|string|max:20',
        'email' => 'required|email|max:255',
        'instagram_url' => 'required|url|max:255',
        'facebook_url' => 'nullable|url|max:255',
        'pix_tipo' => 'required|string|max:20',
        'pix_chave' => 'required|string|max:255',
        'banco_nome' => 'nullable|string|max:100',
        'banco_agencia' => 'nullable|string|max:20',
        'banco_conta' => 'nullable|string|max:30',
        'banco_tipo_conta' => 'nullable|string|max:20',
        'descricao' => 'nullable|string|max:2000',
        'eixos' => 'nullable|array',
        'eixos.*' => 'in:produto,servico,cuidado',
    ], [
        'nome_loja.required' => 'Informe o nome da loja.',
        'responsavel.required' => 'Informe o nome do responsável.',
        'cpf_cnpj.required' => 'Informe o CPF ou CNPJ.',
        'whatsapp.required' => 'Informe o WhatsApp.',
        'email.required' => 'Informe o e-mail.',
        'email.email' => 'Informe um e-mail válido.',
        'instagram_url.required' => 'Informe o endereço do Instagram.',
        'instagram_url.url' => 'O endereço do Instagram deve ser uma URL válida.',
        'facebook_url.url' => 'O endereço do Facebook deve ser uma URL válida.',
        'pix_tipo.required' => 'Selecione o tipo da chave PIX.',
        'pix_chave.required' => 'Informe a chave PIX.',
    ]);

    $solicitacao = LojistasSolicitacao::create($validated);

    try {
        Mail::to($solicitacao->email)->send(new LojistaSolicitacaoRecebida($solicitacao));
    } catch (Throwable $exception) {
        report($exception);
    }

    return back()->with('solicitacao_success',
        'Solicitação enviada! Enviamos um e-mail de confirmação e nossa equipe entrará em contato em até 3 dias úteis.'
    );
})->name('seja-um-expositor.post');

// ─── Agenda de Feiras (público) ───────────────────────────────────────────────
Route::get('/agenda', function (Request $request) {
    $estadoFiltro = strtoupper($request->input('estado', ''));
    $mesFiltro = (int) $request->input('mes', 0);
    $anoFiltro = (int) $request->input('ano', date('Y'));

    $events = Event::where('is_active', true)
        ->where('start_date', '>=', now())
        ->when($estadoFiltro, fn ($q) => $q->where('state', $estadoFiltro))
        ->when($mesFiltro, fn ($q) => $q->whereMonth('start_date', $mesFiltro))
        ->when($anoFiltro, fn ($q) => $q->whereYear('start_date', $anoFiltro))
        ->orderBy('start_date')
        ->paginate(12);

    return view('agenda.index', compact('events', 'estadoFiltro', 'mesFiltro', 'anoFiltro'));
})->name('agenda.index');

Route::get('/agenda/{slug}', function (string $slug) {
    $event = Event::where('slug', $slug)->where('is_active', true)->firstOrFail();
    $event->load(['expositores' => fn ($q) => $q->where('event_expositores.status', 'confirmado')]);

    $otherEvents = Event::where('is_active', true)
        ->where('start_date', '>=', now())
        ->where('id', '!=', $event->id)
        ->orderBy('start_date')
        ->limit(3)
        ->get();

    return view('agenda.show', compact('event', 'otherEvents'));
})->name('agenda.show');

// ─── Blog / Notícias (público) ───────────────────────────────────────────────
Route::get('/blog/{slug}', function (string $slug) {
    $post = Post::published()
        ->where('slug', $slug)
        ->with('author')
        ->firstOrFail();

    $related = Post::published()
        ->where('id', '!=', $post->id)
        ->where('type', $post->type)
        ->orderByDesc('published_at')
        ->limit(3)
        ->get();

    return view('blog.show', compact('post', 'related'));
})->name('blog.show');

Route::get('/feed', function () {
    return view('feed.index');
})->name('feed.index');

// ─── Checkout (convidados veem modal de auth inline) ──────────────────────────
Route::get('/checkout', function () {
    if (! auth()->check()) {
        session()->put('url.intended', route('checkout'));
    }

    return view('checkout');
})->name('checkout');

Route::post('/shipping/quote', [ShippingController::class, 'quote'])
    ->name('shipping.quote');

Route::post('/pagamentos/mercado-pago/webhook', [MercadoPagoPaymentController::class, 'webhook'])
    ->name('mercado-pago.webhook');

Route::get('/pagamentos/mercado-pago/retorno/{reference}', [MercadoPagoPaymentController::class, 'retorno'])
    ->name('mercado-pago.return');

Route::get('/pedido/{reference}/pagar', [MercadoPagoPaymentController::class, 'start'])
    ->name('mercado-pago.pay');

Route::get('/pedido/{reference}', function (string $reference) {
    $order = Order::where('reference', $reference)
        ->with(['items', 'splits.expositor', 'shippings.expositor'])
        ->firstOrFail();

    return view('pedido.show', compact('order'));
})->name('pedido.show');

// ─── Tracking de e-mail marketing ─────────────────────────────────────────────

// Pixel de abertura (1×1 GIF)
Route::get('/mk/o/{token}', function (string $token) {
    $send = EmailCampaignSend::where('tracking_pixel_token', $token)->first();
    if ($send && ! $send->opened_at) {
        $send->update(['opened_at' => now()]);
    }

    $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

    return response($gif, 200, [
        'Content-Type'  => 'image/gif',
        'Cache-Control' => 'no-store, no-cache, must-revalidate',
    ]);
})->name('mk.open');

// Rastreio de clique (redirect)
Route::get('/mk/c/{token}', function (string $token) {
    $send = EmailCampaignSend::where('tracking_pixel_token', $token)->first();
    $url  = request()->query('url', url('/'));

    if ($send && ! $send->clicked_at) {
        $send->update(['clicked_at' => now()]);
    }

    return redirect()->away($url);
})->name('mk.click');

// Descadastro
Route::get('/newsletter/descadastro/{token}', function (string $token) {
    $email      = request()->query('email', '');
    $campaignId = request()->query('campaign', 0);

    $secret   = config('app.marketing_unsubscribe_secret', config('app.key'));
    $expected = hash_hmac('sha256', $email . '|' . $campaignId, $secret);

    if (! hash_equals($expected, $token)) {
        session()->flash('error', 'Link de descadastro inválido ou expirado.');
        return view('newsletter.unsubscribe');
    }

    return view('newsletter.unsubscribe', compact('token', 'email', 'campaignId'));
})->name('newsletter.unsubscribe');

Route::post('/newsletter/descadastro/confirmar', function () {
    $token      = request('token');
    $email      = request('email', '');
    $campaignId = request('campaign', 0);

    $secret   = config('app.marketing_unsubscribe_secret', config('app.key'));
    $expected = hash_hmac('sha256', $email . '|' . $campaignId, $secret);

    abort_unless(hash_equals($expected, $token), 403);

    // Marcar descadastro no envio específico
    EmailCampaignSend::where('campaign_id', $campaignId)
        ->where('email', $email)
        ->whereNull('unsubscribed_at')
        ->update(['unsubscribed_at' => now()]);

    // Desativar na newsletter
    NewsletterSubscriber::where('email', $email)
        ->update(['is_active' => false]);

    session()->flash('success', 'Inscrição cancelada com sucesso. Você não receberá mais comunicações de marketing da Feira Esquerda Livre.');

    return redirect()->route('newsletter.unsubscribe', ['token' => $token, 'email' => $email, 'campaign' => $campaignId]);
})->name('newsletter.unsubscribe.confirm');

// ─── Rastreio de entrega ──────────────────────────────────────────────────────

Route::get('/rastreio/{trackingCode}', function (string $trackingCode) {
    $shipping = OrderShipping::where('tracking_code', strtoupper($trackingCode))
        ->with(['order', 'expositor', 'trackingEvents'])
        ->firstOrFail();

    return view('rastreio.show', compact('shipping'));
})->name('rastreio.show');

// ─── Lojas públicas ───────────────────────────────────────────────────────────
Route::get('/loja/{slug}', function (string $slug) {
    $expositor = Expositor::where('slug', $slug)
        ->where('is_active', true)
        ->firstOrFail();

    $products = Product::where('expositor_id', $expositor->id)
        ->where('is_active', true)
        ->orderBy('sort_order')
        ->orderByDesc('created_at')
        ->get();

    return view('loja.show', compact('expositor', 'products'));
})->name('loja.show');

Route::get('/loja/{slug}/{productSlug}/compartilhar.png', ProductSharePreviewController::class)
    ->name('loja.produto.share-preview');

Route::get('/loja/{slug}/{productSlug}', function (string $slug, string $productSlug) {
    $expositor = Expositor::where('slug', $slug)
        ->where('is_active', true)
        ->firstOrFail();

    $product = Product::where('expositor_id', $expositor->id)
        ->where('slug', $productSlug)
        ->where('is_active', true)
        ->with('faqs')
        ->firstOrFail();

    $otherProducts = Product::where('expositor_id', $expositor->id)
        ->where('id', '!=', $product->id)
        ->where('is_active', true)
        ->limit(4)
        ->get();

    return view('loja.produto', compact('expositor', 'product', 'otherProducts'));
})->name('loja.produto');

// ─── Painel Administrativo ────────────────────────────────────────────────────
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/', Dashboard::class)->name('dashboard');

    Route::middleware('can:configuracoes.visualizar')->group(function () {
        Route::get('/settings', SettingsForm::class)->name('settings.edit');
        Route::get('/settings/mail', MailSettingsForm::class)->name('settings.mail');
        Route::get('/settings/checkout', CheckoutSettingsForm::class)->name('settings.checkout');
    });

    Route::middleware('can:usuarios.visualizar')->group(function () {
        Route::get('/usuarios', UsuarioIndex::class)->name('usuarios.index');
    });

    Route::middleware('can:usuarios.gerenciar')->group(function () {
        Route::get('/usuarios/novo', UsuarioForm::class)->name('usuarios.create');
        Route::get('/usuarios/{user}/editar', UsuarioForm::class)->name('usuarios.edit');
    });

    Route::get('/perfis-acesso', PerfilAcessoIndex::class)
        ->middleware('can:permissoes.gerenciar')
        ->name('permissoes.index');

    Route::middleware('can:cms.visualizar')->group(function () {
        Route::get('/pages', PageIndex::class)->name('pages.index');
        Route::get('/pages/create', PageForm::class)->middleware('can:cms.editar')->name('pages.create');
        Route::get('/pages/{page}/edit', PageForm::class)->middleware('can:cms.editar')->name('pages.edit');

        Route::get('/banners', BannerIndex::class)->name('banners.index');
        Route::get('/banners/create', BannerForm::class)->middleware('can:cms.editar')->name('banners.create');
        Route::get('/banners/{banner}/edit', BannerForm::class)->middleware('can:cms.editar')->name('banners.edit');

        Route::get('/menus', MenuIndex::class)->name('menus.index');
        Route::get('/menus/{menu}/edit', MenuForm::class)->middleware('can:cms.editar')->name('menus.edit');

        Route::get('/media', MediaLibrary::class)->name('media.index');

        Route::get('/posts', PostIndex::class)->name('posts.index');
        Route::get('/posts/create', PostForm::class)->middleware('can:cms.editar')->name('posts.create');
        Route::get('/posts/{post}/edit', PostForm::class)->middleware('can:cms.editar')->name('posts.edit');

        Route::get('/events', EventIndex::class)->name('events.index');
        Route::get('/events/create', EventForm::class)->middleware('can:cms.editar')->name('events.create');
        Route::get('/events/{event}/edit', EventForm::class)->middleware('can:cms.editar')->name('events.edit');

        Route::get('/expositores', ExpositoresIndex::class)->name('expositores.index');
        Route::get('/expositores/{expositor}/edit', ExpositoresForm::class)->middleware('can:cms.editar')->name('expositores.edit');
        Route::get('/expositores/visibilidade', ExpositoresVisibilityIndex::class)->middleware('can:expositores.visibilidade')->name('expositores.visibilidade');

        Route::get('/categorias', CategoriaIndex::class)->name('categorias.index');
        Route::get('/categorias/create', CategoriaForm::class)->middleware('can:cms.editar')->name('categorias.create');
        Route::get('/categorias/{categoria}/edit', CategoriaForm::class)->middleware('can:cms.editar')->name('categorias.edit');
    });

    // Lojistas
    Route::get('/lojistas/solicitacoes', SolicitacaoIndex::class)
        ->middleware('can:lojistas.visualizar')
        ->name('lojistas.solicitacoes');

    Route::get('/pedidos', AdminPedidoIndex::class)
        ->middleware('can:pedidos.visualizar')
        ->name('pedidos.index');

    Route::get('/clientes', ClienteIndex::class)
        ->middleware('can:clientes.visualizar')
        ->name('clientes.index');

    Route::get('/feed/reportes', FeedReportIndex::class)
        ->middleware('can:feed.moderar')
        ->name('feed.reportes');

    // Email Marketing
    Route::middleware('can:email-marketing.gerenciar')->prefix('/email-marketing')->name('email-marketing.')->group(function () {
        Route::get('/', CampaignIndexLivewire::class)->name('index');
        Route::get('/create', CampaignFormLivewire::class)->name('create');
        Route::get('/{id}/edit', CampaignFormLivewire::class)->name('edit');
        Route::get('/{id}/relatorio', CampaignReport::class)->name('report');
    });
});

// ─── Painel do Lojista ────────────────────────────────────────────────────────
Route::middleware(['auth', 'lojista'])->prefix('minha-loja')->name('lojista.')->group(function () {
    Route::get('/', LojistaDashboard::class)->name('dashboard');
    Route::get('/loja', LojaForm::class)->name('loja');

    Route::get('/produtos', ProdutoIndex::class)->name('produtos.index');
    Route::get('/produtos/novo', ProdutoForm::class)->name('produtos.create');
    Route::get('/produtos/{product}/editar', ProdutoForm::class)->name('produtos.edit');
    Route::get('/produtos/{product}/imagem-compartilhamento', ProductShareImageController::class)->name('produtos.share-image');

    Route::get('/feed', FeedPostIndex::class)->name('feed.index');

    Route::get('/pedidos', LojistaPedidoIndex::class)->name('pedidos.index');
    Route::get('/exposicao', LojistaExposicaoIndex::class)->name('exposicao.index');
});

// ─── Minha Conta (cliente) ─────────────────────────────────────────────────────
Route::middleware('auth')->prefix('minha-conta')->name('cliente.')->group(function () {
    Route::redirect('/', '/minha-conta/pedidos');

    Route::get('/pedidos', ClientePedidoIndex::class)->name('pedidos.index');

    Route::get('/enderecos', EnderecoIndex::class)->name('enderecos.index');
    Route::get('/enderecos/novo', EnderecoForm::class)->name('enderecos.create');
    Route::get('/enderecos/{endereco}/editar', EnderecoForm::class)->name('enderecos.edit');
});

// ─── Autenticação ─────────────────────────────────────────────────────────────
require __DIR__.'/auth.php';
