<?php

namespace App\Services;

use App\Mail\AvaCertificateMail;
use App\Models\Ava\AvaEnrollment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AvaCertificateService
{
    public function generate(AvaEnrollment $enrollment): void
    {
        $pdf  = Pdf::loadView('ava.certificado-pdf', ['enrollment' => $enrollment])
                   ->setPaper('a4', 'landscape');

        $path = 'ava/certificates/' . $enrollment->id . '.pdf';

        Storage::put($path, $pdf->output());

        $enrollment->update(['certificate_path' => $path]);

        try {
            Mail::to($enrollment->user->email)->send(
                new AvaCertificateMail($enrollment)
            );
        } catch (\Throwable) {
            // Falha no envio não deve impedir o acesso ao certificado
        }
    }
}
