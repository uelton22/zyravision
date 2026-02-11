<?php

namespace App\Services\Manufacturers\Profiles;

class TecvozProfile extends AbstractDahuaProfile
{
    public function getSlug(): string
    {
        return 'tecvoz';
    }

    public function getDisplayName(): string
    {
        return 'Tecvoz';
    }

    public function getKnownModels(): array
    {
        return [
            'T1-LTVI04', 'T1-LTVI08', 'T1-LTVI16',
            'T1-STVI04', 'T1-STVI08', 'T1-STVI16',
            'TV-ICB212', 'TV-ICB412',
            'TV-ICB224', 'TV-ICB424',
        ];
    }

    public function getChannelRange(): array
    {
        return [1, 16];
    }
}
