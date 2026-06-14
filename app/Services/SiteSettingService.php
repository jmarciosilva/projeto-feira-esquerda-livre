<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\DB;

class SiteSettingService
{
    /**
     * Retorna o registro singleton de configurações do site.
     */
    public function get(): SiteSetting
    {
        return SiteSetting::instance();
    }

    /**
     * Persiste as configurações gerais do site.
     *
     * @param array<string, mixed> $data
     */
    public function save(array $data): SiteSetting
    {
        DB::beginTransaction();

        try {
            $setting = SiteSetting::instance();
            $setting->fill($data);
            $setting->save();

            DB::commit();

            return $setting;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
