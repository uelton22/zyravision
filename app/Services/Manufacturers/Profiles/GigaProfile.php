<?php

namespace App\Services\Manufacturers\Profiles;

class GigaProfile extends AbstractDahuaProfile
{
    public function getSlug(): string
    {
        return 'giga';
    }

    public function getDisplayName(): string
    {
        return 'Giga Security';
    }

    public function getKnownModels(): array
    {
        return [
            'GS0180', 'GS0181', 'GS0182', 'GS0183',
            'GS0190', 'GS0191', 'GS0192',
            'GS0480', 'GS0481',
            'GS0084', 'GS0085',
        ];
    }

    public function getChannelRange(): array
    {
        return [1, 32];
    }
}
