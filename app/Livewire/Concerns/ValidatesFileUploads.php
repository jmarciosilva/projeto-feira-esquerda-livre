<?php

namespace App\Livewire\Concerns;

use League\Flysystem\UnableToRetrieveMetadata;

trait ValidatesFileUploads
{
    /**
     * Verifica se um TemporaryUploadedFile existe e tem tamanho válido.
     * Usa filesize() nativo em vez de Flysystem::size() que não tem try-catch
     * no FilesystemAdapter do Laravel e estoura em arquivos ausentes.
     */
    protected function checkUploadedFile(mixed $file, int $maxKb, string $errorKey): bool
    {
        if (! $file) {
            return true;
        }

        if (! $file->exists()) {
            $this->addError($errorKey, 'O arquivo não pôde ser carregado. Por favor, tente novamente.');
            return false;
        }

        try {
            $realPath = $file->getRealPath();
            $size     = @filesize($realPath);
        } catch (\Throwable) {
            $size = false;
        }

        if ($size === false) {
            $this->addError($errorKey, 'Não foi possível verificar o arquivo. Tente novamente.');
            return false;
        }

        if ($size > $maxKb * 1024) {
            $maxMb = round($maxKb / 1024, 0);
            $this->addError($errorKey, "O arquivo deve ter no máximo {$maxMb}MB.");
            return false;
        }

        return true;
    }
}
