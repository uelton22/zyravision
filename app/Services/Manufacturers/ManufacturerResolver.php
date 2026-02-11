<?php

namespace App\Services\Manufacturers;

use App\Contracts\ManufacturerProfile;
use App\Services\Manufacturers\Profiles\DahuaProfile;
use App\Services\Manufacturers\Profiles\GenericProfile;
use App\Services\Manufacturers\Profiles\GigaProfile;
use App\Services\Manufacturers\Profiles\HikvisionProfile;
use App\Services\Manufacturers\Profiles\IntelbrasProfile;
use App\Services\Manufacturers\Profiles\TecvozProfile;
use Illuminate\Support\Str;

class ManufacturerResolver
{
    /** @var array<string, ManufacturerProfile> */
    private array $profiles = [];

    public function __construct()
    {
        $this->registerDefaults();
    }

    public function getProfile(string $manufacturer): ManufacturerProfile
    {
        $slug = Str::slug($manufacturer);

        return $this->profiles[$slug] ?? $this->profiles['generic'];
    }

    public function register(ManufacturerProfile $profile): void
    {
        $this->profiles[$profile->getSlug()] = $profile;
    }

    /** @return array<string, ManufacturerProfile> */
    public function getAllProfiles(): array
    {
        return $this->profiles;
    }

    /**
     * Returns list formatted for frontend select dropdowns.
     */
    public function getManufacturerList(): array
    {
        return collect($this->profiles)
            ->map(fn (ManufacturerProfile $p) => [
                'value' => $p->getSlug(),
                'label' => $p->getDisplayName(),
                'onvif' => $p->supportsOnvif(),
                'ptz' => $p->supportsPtz(),
                'audio' => $p->supportsAudio(),
            ])
            ->sortBy('label')
            ->values()
            ->all();
    }

    private function registerDefaults(): void
    {
        $classes = [
            IntelbrasProfile::class,
            HikvisionProfile::class,
            DahuaProfile::class,
            TecvozProfile::class,
            GigaProfile::class,
            GenericProfile::class,
        ];

        foreach ($classes as $class) {
            $profile = new $class;
            $this->profiles[$profile->getSlug()] = $profile;
        }
    }
}
