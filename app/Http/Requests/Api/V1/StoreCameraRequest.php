<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreCameraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'device_id' => 'nullable|uuid|exists:devices,id',
            'channel_number' => 'nullable|integer|min:1|max:128',
            'channel_type' => 'required|string|in:analog,ip,direct',
            'manufacturer' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:255',
            'address_type' => 'required_without:device_id|nullable|string|in:ipv4,ipv6,ddns,hostname',
            'address' => 'required_without:device_id|nullable|string|max:255',
            'port' => 'integer|min:1|max:65535',
            'secondary_address' => 'nullable|string|max:255',
            'transport' => 'string|in:tcp,udp,auto',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'stream_type' => 'string|in:main,sub,mobile',
            'rtsp_path' => 'nullable|string|max:500',
            'has_ptz' => 'boolean',
            'has_audio' => 'boolean',
            'is_active' => 'boolean',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'ddns_provider' => 'nullable|string|max:100',
            'ddns_hostname' => 'nullable|string|max:255',
            'ipv6_preferred' => 'boolean',
        ];
    }
}
