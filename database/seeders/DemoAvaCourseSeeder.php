<?php

namespace Database\Seeders;

use App\Enums\AvaEnrollmentStatus;
use App\Enums\ItemType;
use App\Enums\Modality;
use App\Enums\PriceType;
use App\Enums\UserRole;
use App\Models\Ava\AvaCourse;
use App\Models\Ava\AvaEnrollment;
use App\Models\Ava\AvaLessonProgress;
use App\Models\Ava\AvaModule;
use App\Models\ContentCategory;
use App\Models\Expositor;
use App\Models\User;
use Database\Seeders\Concerns\SincronizaOfertaDoItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DemoAvaCourseSeeder extends Seeder
{
    use SincronizaOfertaDoItem;

    public function run(): void
    {
        $expositor = Expositor::query()
            ->where('email', 'tech@teste.com')
            ->orWhere('name', 'Tecnologia Solidaria')
            ->orWhere('name', 'Tecnologia Solidária')
            ->first();

        if (! $expositor) {
            $this->command->warn('DemoAvaCourseSeeder: expositor Tecnologia Solidaria nao encontrado. Rode o ServicoSeeder antes.');

            return;
        }

        $cliente = User::updateOrCreate(
            ['email' => 'cliente.curso@teste.com'],
            [
                'name' => 'Cliente Curso Demo',
                'whatsapp' => '(11) 97777-0101',
                'password' => 'password',
                'role' => UserRole::User,
                'is_active' => true,
            ],
        );

        if (method_exists($cliente, 'syncRoles')) {
            $cliente->syncRoles([UserRole::User->spatieRole()]);
        }

        $cliente->customerProfile()->firstOrCreate([]);

        $categoryId = ContentCategory::query()
            ->where('slug', 'aulas-e-workshops')
            ->value('id');

        $imagePath = $this->storeCourseImage();

        $offer = $this->semearItemComOferta(
            ['slug' => 'curso-online-de-informatica-popular'],
            [
                'expositor_id' => $expositor->id,
                'category_id' => $categoryId,
                'item_type' => ItemType::Servico,
                'name' => 'Curso Online de Informática Popular',
                'description' => 'Curso introdutório para clientes testarem a área de aprendizado, concluir aulas e emitir certificado na Feira Esquerda Livre.',
                'image_path' => $imagePath,
                'images' => [[
                    'thumb' => $imagePath,
                    'medium' => $imagePath,
                ]],
                'price' => 0.01,
                'price_type' => PriceType::Fixo,
                'modality' => Modality::Online,
                'duration_min' => 150,
                'has_stock' => true,
                'stock_quantity' => 100,
                'is_featured' => true,
                'is_active' => true,
                'is_digital' => true,
                'sort_order' => 1,
            ],
            $expositor->id,
        );

        $product = $offer->product;

        $course = AvaCourse::updateOrCreate(
            ['product_id' => $product->id],
            [
                'intro_video_url' => null,
                'requirements' => 'Celular, computador ou tablet com acesso a internet.',
                'what_youll_learn' => 'Organizar arquivos, usar e-mail com segurança, atender melhor pelo WhatsApp e divulgar sua banca nas redes sociais.',
                'level' => 'iniciante',
                'estimated_hours' => 2.5,
                'access_duration_days' => 365,
                'is_drip' => false,
                'certificate_enabled' => true,
                'published_at' => now()->subDay(),
            ],
        );

        $course->modules()->get()->each->delete();

        $this->createModule($course, 1, 'Começando no digital', 'Primeiros passos para usar a internet com autonomia.', [
            [
                'title' => 'Boas-vindas ao curso',
                'description' => 'Entenda como o curso funciona e como marcar cada aula como concluída.',
                'text' => "Bem-vinda e bem-vindo ao curso de Informática Popular.\n\nEste curso foi criado como demonstração do ambiente de aprendizado da Feira Esquerda Livre. Leia cada aula, marque como concluída e avance até o final para liberar o certificado.\n\nA proposta é mostrar que um expositor pode vender um curso online e o cliente pode estudar dentro da própria plataforma.",
            ],
            [
                'title' => 'Organização básica do celular e computador',
                'description' => 'Aprenda uma rotina simples para guardar arquivos e encontrar informações importantes.',
                'text' => "Uma boa organização digital começa com nomes claros e pastas simples.\n\nCrie uma pasta para documentos, outra para fotos da feira e outra para comprovantes. Evite deixar arquivos importantes apenas no WhatsApp.\n\nQuando salvar um arquivo, use nomes como feira-sao-paulo-contrato.pdf ou fotos-produtos-agosto. Isso facilita a busca e reduz retrabalho.",
            ],
        ]);

        $this->createModule($course, 2, 'Ferramentas para vender melhor', 'Uso prático de canais digitais no atendimento e divulgação.', [
            [
                'title' => 'E-mail, WhatsApp e atendimento ao cliente',
                'description' => 'Cuidados simples para responder clientes com clareza e profissionalismo.',
                'text' => "No atendimento online, rapidez e clareza contam muito.\n\nConfirme o nome do cliente, explique prazos, valores e formas de entrega. No e-mail, use um assunto objetivo. No WhatsApp, evite mensagens muito longas e sempre finalize com o próximo passo combinado.\n\nExemplo: Recebi seu pedido. Vou separar o produto e te envio a confirmação até as 16h.",
            ],
            [
                'title' => 'Divulgação simples nas redes sociais',
                'description' => 'Como apresentar um produto, serviço ou curso de forma direta.',
                'text' => "Uma boa postagem responde três perguntas: o que é, para quem serve e como comprar.\n\nUse uma foto bem iluminada, escreva uma legenda curta e coloque uma chamada clara. Para cursos, explique o que a pessoa vai aprender, quanto tempo dura e se há certificado.\n\nAo concluir esta aula, o sistema deve liberar o certificado automaticamente.",
            ],
        ]);

        $enrollment = AvaEnrollment::updateOrCreate(
            [
                'user_id' => $cliente->id,
                'course_id' => $course->id,
            ],
            [
                'order_split_id' => null,
                'status' => AvaEnrollmentStatus::Active,
                'enrolled_at' => now(),
                'expires_at' => now()->addYear(),
                'completed_at' => null,
                'completion_percent' => 0,
                'certificate_path' => null,
                'last_accessed_at' => null,
            ],
        );

        AvaLessonProgress::where('enrollment_id', $enrollment->id)->delete();

        $this->command->info('DemoAvaCourseSeeder: curso online, cliente teste e matricula criados.');
        $this->command->line('  Cliente: cliente.curso@teste.com / password');
        $this->command->line('  Curso: Curso Online de Informatica Popular');
    }

    /**
     * @param  array<int, array{title: string, description: string, text: string}>  $lessons
     */
    private function createModule(AvaCourse $course, int $sortOrder, string $title, string $description, array $lessons): void
    {
        $module = AvaModule::create([
            'course_id' => $course->id,
            'title' => $title,
            'description' => $description,
            'sort_order' => $sortOrder,
            'is_visible' => true,
        ]);

        foreach ($lessons as $index => $lesson) {
            $module->lessons()->create([
                'title' => $lesson['title'],
                'description' => $lesson['description'],
                'content_type' => 'texto',
                'text_content' => $lesson['text'],
                'is_preview' => $index === 0 && $sortOrder === 1,
                'is_visible' => true,
                'sort_order' => $index + 1,
                'drip_day' => null,
            ]);
        }
    }

    private function storeCourseImage(): string
    {
        $path = 'demo-products/curso-online-de-informatica-popular.svg';

        Storage::disk('public')->put($path, $this->courseSvg());

        return $path;
    }

    private function courseSvg(): string
    {
        $title = e('Curso Online de Informática Popular');

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="900" height="560" viewBox="0 0 900 560" role="img" aria-label="{$title}">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#fff8d7"/>
      <stop offset="0.55" stop-color="#f4e294"/>
      <stop offset="1" stop-color="#eba000"/>
    </linearGradient>
    <filter id="shadow" x="-20%" y="-20%" width="140%" height="140%">
      <feDropShadow dx="0" dy="18" stdDeviation="20" flood-color="#3d3000" flood-opacity="0.22"/>
    </filter>
  </defs>
  <rect width="900" height="560" fill="url(#bg)"/>
  <circle cx="160" cy="100" r="120" fill="#ffffff" opacity="0.24"/>
  <circle cx="760" cy="470" r="170" fill="#ffffff" opacity="0.26"/>
  <path d="M0 395 C150 332 252 432 398 360 C548 286 686 315 900 210 L900 560 L0 560 Z" fill="#ffffff" opacity="0.28"/>
  <g filter="url(#shadow)">
    <rect x="188" y="112" width="524" height="318" rx="34" fill="#fffdf2"/>
    <rect x="230" y="156" width="440" height="230" rx="20" fill="#3d3000"/>
    <rect x="258" y="184" width="384" height="174" rx="14" fill="#fff8d7"/>
    <circle cx="450" cy="270" r="54" fill="#eba000"/>
    <path d="M436 240 L486 270 L436 300 Z" fill="#fffdf2"/>
    <rect x="366" y="414" width="168" height="26" rx="13" fill="#3d3000"/>
    <rect x="326" y="452" width="248" height="32" rx="16" fill="#fffdf2"/>
  </g>
  <g>
    <rect x="80" y="74" width="224" height="56" rx="28" fill="#fffdf2" opacity="0.94"/>
    <text x="192" y="108" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="19" font-weight="900" fill="#3d3000">Curso online</text>
    <rect x="596" y="76" width="216" height="56" rx="28" fill="#3d3000" opacity="0.95"/>
    <text x="704" y="110" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="19" font-weight="900" fill="#fff8d7">Com certificado</text>
  </g>
  <text x="450" y="512" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="28" font-weight="900" fill="#3d3000">Informática Popular</text>
</svg>
SVG;
    }
}
