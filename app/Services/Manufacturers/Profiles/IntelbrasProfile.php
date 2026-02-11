<?php

namespace App\Services\Manufacturers\Profiles;

class IntelbrasProfile extends AbstractDahuaProfile
{
    public function getSlug(): string
    {
        return 'intelbras';
    }

    public function getDisplayName(): string
    {
        return 'Intelbras';
    }

    public function getKnownModels(): array
    {
        return [
            'MHDX 1004-C', 'MHDX 1008-C', 'MHDX 1016-C',
            'MHDX 3004-C', 'MHDX 3008-C', 'MHDX 3016-C',
            'MHDX 5016', 'MHDX 5032', 'MHDX 7016', 'MHDX 7032',
            'NVD 1304', 'NVD 1308', 'NVD 1316', 'NVD 3308-P',
            'NVD 5124', 'NVD 7132',
            'VIP 1130 B', 'VIP 1230 B', 'VIP 3230 B',
            'VIP 5280 Z', 'VIP 7280 Z',
            'VHD 1120 B', 'VHD 1220 B', 'VHD 3230 B',
        ];
    }

    public function getChannelRange(): array
    {
        return [1, 32];
    }
}
