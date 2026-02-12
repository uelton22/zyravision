<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:dvr,nvr,encoder,camera,hybrid',
            'manufacturer' => 'required|string|max:100',
            'model' => 'nullable|string|max:255',
            'firmware_version' => 'nullable|string|max:100',
            'address_type' => 'required|string|in:ipv4,ipv6,ddns,hostname',
            'address' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'http_port' => 'integer|min:1|max:65535',
            'rtmp_port' => 'integer|min:1|max:65535',
            'secondary_address' => 'nullable|string|max:255',
            'transport' => 'required|string|in:tcp,udp,auto',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'total_channels' => 'required|integer|min:1|max:128',
            'onvif_supported' => 'boolean',
            'onvif_profile' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'location' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'ddns_provider' => 'nullable|string|max:100',
            'ddns_hostname' => 'nullable|string|max:255',
            'ipv6_preferred' => 'boolean',
        ];
    }
}
