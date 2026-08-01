<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AddressRequest;
use App\Http\Resources\Api\V1\AddressResource;
use App\Models\CustomerAddress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EnderecoController extends Controller
{
    /** GET /api/v1/enderecos */
    public function index(Request $request): AnonymousResourceCollection
    {
        $addresses = $request->user()->addresses()->orderByDesc('is_default')->get();

        return AddressResource::collection($addresses);
    }

    /** POST /api/v1/enderecos */
    public function store(AddressRequest $request): AddressResource
    {
        $data = $request->validated();
        $hasAddresses = $request->user()->addresses()->exists();

        $address = $request->user()->addresses()->create([
            ...$data,
            'estado' => strtoupper($data['estado']),
            'is_default' => $data['is_default'] ?? ! $hasAddresses,
        ]);

        return new AddressResource($address);
    }

    /** PUT /api/v1/enderecos/{endereco} */
    public function update(AddressRequest $request, CustomerAddress $endereco): AddressResource
    {
        abort_unless($endereco->user_id === $request->user()->id, 403);

        $data = $request->validated();
        $endereco->update([
            ...$data,
            'estado' => strtoupper($data['estado']),
        ]);

        return new AddressResource($endereco);
    }

    /** DELETE /api/v1/enderecos/{endereco} */
    public function destroy(Request $request, CustomerAddress $endereco): \Illuminate\Http\Response
    {
        abort_unless($endereco->user_id === $request->user()->id, 403);

        $endereco->delete();

        return response()->noContent();
    }
}
