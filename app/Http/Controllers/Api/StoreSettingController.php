<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSettingRequest;
use App\Http\Resources\StoreSettingResource;
use App\Models\StoreSetting;
use Illuminate\Support\Str;

class StoreSettingController extends Controller
{
    public function show()
    {
        $settings = StoreSetting::first();

        if (!$settings) {
            $settings = StoreSetting::create([
                'id' => (string) Str::orderedUuid(),
                'name' => 'Toko',
            ]);
        }

        $this->authorize('view', $settings);

        return new StoreSettingResource($settings);
    }

    public function update(StoreSettingRequest $request)
    {
        $settings = StoreSetting::first();

        if (!$settings) {
            $settings = new StoreSetting(['id' => (string) Str::orderedUuid()]);
        }

        $this->authorize('update', $settings);

        $settings->fill($request->validated());
        $settings->save();

        return new StoreSettingResource($settings);
    }
}
