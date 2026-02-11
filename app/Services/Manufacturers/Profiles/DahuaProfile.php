<?php

namespace App\Services\Manufacturers\Profiles;

class DahuaProfile extends AbstractDahuaProfile
{
    public function getSlug(): string
    {
        return 'dahua';
    }

    public function getDisplayName(): string
    {
        return 'Dahua';
    }

    public function getKnownModels(): array
    {
        return [
            'XVR5104HS', 'XVR5108HS', 'XVR5116HS', 'XVR5216AN',
            'NVR4104HS', 'NVR4108HS', 'NVR4116HS', 'NVR4232',
            'DH-HAC-HDW1200T', 'DH-HAC-HFW1200T',
            'DH-IPC-HDW2431T', 'DH-IPC-HFW2431T',
            'DH-SD49425XB-HNR',
        ];
    }

    public function getChannelRange(): array
    {
        return [1, 64];
    }
}
