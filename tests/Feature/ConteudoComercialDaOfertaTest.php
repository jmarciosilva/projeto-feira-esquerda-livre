<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Lojista\Produtos\ProdutoForm;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * CAT-DOM-02E — a imagem comercial: onde é escrita, de onde é lida, como é
 * removida.
 *
 * O assunto que mais aparece aqui é o mesmo do §17 da 02D, agora do lado da
 * leitura: **fallback é decisão de exibição, nunca persistência**. Mostrar a
 * imagem canônica quando a oferta não tem uma não pode, em hipótese alguma,
 * gravar aquele caminho dentro de `ProductOffer.images` — seria recriar o
 * compartilhamento de arquivo físico que a fase anterior existiu para impedir.
 */
class ConteudoComercialDaOfertaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');
    }

    private function lojista(): User
    {
        $user = User::factory()->create(['role' => UserRole::Lojista, 'is_active' => true]);
        $user->assignRole('lojista');

        return $user;
    }

    /** @return array{0: User, 1: Product, 2: ProductOffer} */
    private function cenario(): array
    {
        $lojista = $this->lojista();
        $expositor = Expositor::factory()->create(['user_id' => $lojista->id]);
        $produto = Product::factory()->create(['expositor_id' => $expositor->id]);

        return [$lojista, $produto, $produto->offers()->sole()];
    }

    private function arquivo(string $nome): string
    {
        Storage::disk('public')->put("products/{$nome}", "bytes-de-{$nome}");

        return "products/{$nome}";
    }

    // ----------------------------------------------------- fallback (leitura)

    public function test_com_imagem_da_oferta_e_da_canonica_mostra_a_da_oferta(): void
    {
        [, $produto, $oferta] = $this->cenario();

        $produto->forceFill(['images' => [['thumb' => 'products/c.webp', 'medium' => 'products/c.webp']]])->save();
        $oferta->update(['images' => [['thumb' => 'products/o.webp', 'medium' => 'products/o.webp']]]);

        $this->assertSame('products/o.webp', $oferta->fresh()->imagensParaExibicao()[0]['medium']);
    }

    public function test_sem_imagem_da_oferta_cai_para_a_canonica(): void
    {
        [, $produto, $oferta] = $this->cenario();

        $produto->forceFill(['images' => [['thumb' => 'products/c.webp', 'medium' => 'products/c.webp']]])->save();

        $this->assertSame('products/c.webp', $oferta->fresh()->imagensParaExibicao()[0]['medium']);
    }

    public function test_sem_imagem_da_oferta_e_sem_canonica_cai_para_o_espelho_legado(): void
    {
        [, $produto, $oferta] = $this->cenario();

        $produto->forceFill(['images' => null, 'image_path' => 'products/legado.webp'])->save();

        $this->assertSame('products/legado.webp', $oferta->fresh()->imagensParaExibicao()[0]['medium']);
    }

    public function test_sem_nenhuma_imagem_nao_ha_o_que_exibir(): void
    {
        [, $produto, $oferta] = $this->cenario();

        $produto->forceFill(['images' => null, 'image_path' => null])->save();

        $this->assertSame([], $oferta->fresh()->imagensParaExibicao());
        $this->assertNull($oferta->fresh()->urlDaImagemPrincipal());
    }

    /** O ponto que separa fallback de write-through. */
    public function test_o_fallback_nao_grava_nada(): void
    {
        [, $produto, $oferta] = $this->cenario();

        $produto->forceFill(['images' => [['thumb' => 'products/c.webp', 'medium' => 'products/c.webp']]])->save();

        $oferta->fresh()->imagensParaExibicao();
        $oferta->fresh()->urlDaImagemPrincipal();

        $this->assertNull($oferta->fresh()->images);
        $this->assertDatabaseHas('product_offers', ['id' => $oferta->id, 'images' => null]);
    }

    // ------------------------------------------------------- writer (escrita)

    public function test_upload_do_lojista_nao_toca_na_imagem_canonica(): void
    {
        [$lojista, $produto, $oferta] = $this->cenario();

        $canonica = $this->arquivo('canonica.webp');
        $produto->forceFill([
            'images' => [['thumb' => $canonica, 'medium' => $canonica]],
            'image_path' => $canonica,
        ])->save();

        Livewire::actingAs($lojista)
            ->test(ProdutoForm::class, ['product' => $produto])
            ->set('upload1', UploadedFile::fake()->image('minha.jpg'))
            ->call('save');

        $daOferta = $oferta->fresh()->images;

        $this->assertNotEmpty($daOferta);
        $this->assertNotSame($canonica, $daOferta[0]['medium']);

        $produto->refresh();
        $this->assertSame($canonica, $produto->images[0]['medium']);
        $this->assertSame($canonica, $produto->image_path);
        Storage::disk('public')->assertExists($canonica);
    }

    /**
     * O invariante do §17 continua valendo depois do cutover: nenhum caminho
     * de arquivo aparece dos dois lados.
     */
    public function test_produto_e_oferta_continuam_sem_compartilhar_arquivo(): void
    {
        [$lojista, $produto, $oferta] = $this->cenario();

        $canonica = $this->arquivo('canonica.webp');
        $produto->forceFill(['images' => [['thumb' => $canonica, 'medium' => $canonica]]])->save();

        Livewire::actingAs($lojista)
            ->test(ProdutoForm::class, ['product' => $produto])
            ->set('upload1', UploadedFile::fake()->image('minha.jpg'))
            ->call('save');

        $paths = fn (array $entradas) => collect($entradas)
            ->flatMap(fn ($e) => [$e['thumb'] ?? null, $e['medium'] ?? null])
            ->filter()->unique()->all();

        $this->assertEmpty(array_intersect(
            $paths($produto->fresh()->images ?? []),
            $paths($oferta->fresh()->images ?? []),
        ));
    }

    // ---------------------------------------------------------- remoção (§42)

    /**
     * O teste crítico da fase.
     *
     * `ImageService::delete()` apaga por caminho e não conta referências
     * (M-05). Se a remoção comercial alcançasse a imagem canônica, o lojista
     * apagaria a foto do item para todo mundo — silenciosamente e sem
     * recuperação.
     */
    public function test_remover_a_imagem_da_oferta_preserva_a_canonica(): void
    {
        [$lojista, $produto, $oferta] = $this->cenario();

        $canonica = $this->arquivo('canonica.webp');
        $comercial = $this->arquivo('comercial.webp');

        $produto->forceFill(['images' => [['thumb' => $canonica, 'medium' => $canonica]]])->save();
        $oferta->update(['images' => [['thumb' => $comercial, 'medium' => $comercial]]]);

        Livewire::actingAs($lojista)
            ->test(ProdutoForm::class, ['product' => $produto])
            ->call('removeImage', 0);

        // A imagem comercial saiu do banco e do disco.
        $this->assertSame([], $oferta->fresh()->images);
        Storage::disk('public')->assertMissing($comercial);

        // A canônica não foi tocada, nem no banco nem no filesystem.
        $produto->refresh();
        $this->assertSame($canonica, $produto->images[0]['medium']);
        Storage::disk('public')->assertExists($canonica);
    }

    /**
     * O caso perigoso: a oferta veio de um backfill antigo e ainda referencia o
     * mesmo arquivo da canônica. Remover não pode levar o arquivo junto.
     */
    public function test_remover_nao_apaga_arquivo_que_a_canonica_ainda_referencia(): void
    {
        [$lojista, $produto, $oferta] = $this->cenario();

        $compartilhado = $this->arquivo('compartilhado.webp');

        $produto->forceFill(['images' => [['thumb' => $compartilhado, 'medium' => $compartilhado]]])->save();
        $oferta->update(['images' => [['thumb' => $compartilhado, 'medium' => $compartilhado]]]);

        Livewire::actingAs($lojista)
            ->test(ProdutoForm::class, ['product' => $produto])
            ->call('removeImage', 0);

        $this->assertSame([], $oferta->fresh()->images);
        Storage::disk('public')->assertExists($compartilhado);
        $this->assertSame($compartilhado, $produto->fresh()->images[0]['medium']);
    }

    // -------------------------------------------------------- página pública

    public function test_a_pagina_da_loja_exibe_a_imagem_da_oferta(): void
    {
        [, $produto, $oferta] = $this->cenario();

        $canonica = $this->arquivo('canonica.webp');
        $comercial = $this->arquivo('comercial.webp');

        $produto->forceFill(['images' => [['thumb' => $canonica, 'medium' => $canonica]]])->save();
        $oferta->update(['images' => [['thumb' => $comercial, 'medium' => $comercial]]]);

        $this->get(route('loja.produto', [$oferta->expositor->slug, $produto->slug]))
            ->assertOk()
            ->assertSee($comercial)
            ->assertDontSee($canonica);
    }

    public function test_a_pagina_da_loja_cai_para_a_canonica_quando_a_oferta_nao_tem_imagem(): void
    {
        [, $produto, $oferta] = $this->cenario();

        $canonica = $this->arquivo('canonica.webp');
        $produto->forceFill(['images' => [['thumb' => $canonica, 'medium' => $canonica]]])->save();

        $this->get(route('loja.produto', [$oferta->expositor->slug, $produto->slug]))
            ->assertOk()
            ->assertSee($canonica);

        // E continua sem gravar nada na oferta.
        $this->assertNull($oferta->fresh()->images);
    }
}
