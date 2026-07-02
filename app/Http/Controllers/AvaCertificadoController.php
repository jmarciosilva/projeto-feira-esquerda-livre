<?php

namespace App\Http\Controllers;

use App\Models\Ava\AvaEnrollment;
use App\Services\AvaCertificateService;
use Illuminate\Support\Facades\Storage;

class AvaCertificadoController extends Controller
{
    public function __construct(private readonly AvaCertificateService $service) {}

    public function download(AvaEnrollment $enrollment): \Symfony\Component\HttpFoundation\Response
    {
        if ($enrollment->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $enrollment->isCompleted()) {
            abort(403, 'Certificado disponível apenas após concluir 100% do curso.');
        }

        // Gera se ainda não existe
        if (! $enrollment->certificate_path || ! Storage::exists($enrollment->certificate_path)) {
            $this->service->generate($enrollment);
            $enrollment->refresh();
        }

        return Storage::download(
            $enrollment->certificate_path,
            'Certificado — ' . $enrollment->course->product->name . '.pdf'
        );
    }
}
