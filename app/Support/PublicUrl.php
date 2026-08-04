<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * `Storage::url()` do disco público retorna caminho relativo (ex.:
 * "/storage/produtos/x.jpg"), porque FILESYSTEM_PUBLIC_URL não é
 * absoluto — funciona no navegador (resolve contra a origem da página),
 * mas quebra em qualquer cliente sem "origem", como o app mobile. `url()`
 * torna o caminho absoluto usando o host da requisição atual (o mesmo
 * host que o cliente já usou para chamar a API), o que também resolve
 * corretamente em dev via emulador (10.0.2.2), dispositivo físico (IP da
 * rede) ou produção, sem precisar de configuração extra por ambiente.
 */
final class PublicUrl
{
    public static function for(?string $path): ?string
    {
        return $path ? url(Storage::url($path)) : null;
    }
}
