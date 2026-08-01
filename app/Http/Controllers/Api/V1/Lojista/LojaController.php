<?php

namespace App\Http\Controllers\Api\V1\Lojista;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Lojista\LojaRequest;
use App\Http\Resources\Api\V1\ExpositorResource;
use App\Models\Expositor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LojaController extends Controller
{
    /** GET /api/v1/lojista/loja */
    public function show(Request $request): ExpositorResource
    {
        return new ExpositorResource($request->user()->expositor);
    }

    /**
     * PUT /api/v1/lojista/loja (aceita spoofing _method=PUT via POST multipart
     * quando enviar logo/banner, já que PHP não popula uploads em requests PUT reais).
     */
    public function update(LojaRequest $request): ExpositorResource
    {
        $expositor = $request->user()->expositor;
        abort_unless($expositor, 404, 'Nenhuma loja vinculada à sua conta.');

        $data = $request->validated();
        unset($data['logo'], $data['banner'], $data['slug']);
        $data['state'] = isset($data['state']) ? strtoupper($data['state']) : $expositor->state;

        if ($slug = $request->validated('slug')) {
            $candidate = Str::slug($slug);
            if (! Expositor::where('slug', $candidate)->where('id', '!=', $expositor->id)->exists()) {
                $data['slug'] = $candidate;
            }
        }

        if ($request->hasFile('logo')) {
            if ($expositor->logo_path) {
                Storage::disk('public')->delete($expositor->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('expositores/logos', 'public');
        }

        if ($request->hasFile('banner')) {
            if ($expositor->image_path) {
                Storage::disk('public')->delete($expositor->image_path);
            }
            $data['image_path'] = $request->file('banner')->store('expositores/banners', 'public');
        }

        $expositor->update($data);

        return new ExpositorResource($expositor->fresh());
    }
}
