<?php

namespace App\Enums;

enum HotspotAccountStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Suspended = 'suspended';
}
