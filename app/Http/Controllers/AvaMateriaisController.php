<?php

namespace App\Http\Controllers;

use App\Models\Ava\AvaEnrollment;
use App\Models\Ava\AvaLessonMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AvaMateriaisController extends Controller
{
    public function download(Request $request, AvaLessonMaterial $material): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Link expirado ou inválido.');
        }

        // Verifica que o usuário tem matrícula ativa no curso que contém esta aula
        $hasAccess = AvaEnrollment::where('user_id', auth()->id())
            ->whereHas('course.modules.lessons', fn ($q) => $q->where('id', $material->lesson_id))
            ->get()
            ->contains(fn ($enrollment) => $enrollment->isAccessible());

        if (! $hasAccess) {
            abort(403, 'Acesso negado a este material.');
        }

        if (! Storage::exists($material->file_path)) {
            abort(404, 'Arquivo não encontrado.');
        }

        return Storage::download($material->file_path, $material->title . '.' . pathinfo($material->file_path, PATHINFO_EXTENSION));
    }
}
