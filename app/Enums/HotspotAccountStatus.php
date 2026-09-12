<?php

namespace App\Enums;

enum HotspotAccountStatus: string
{
    case Active = 'active';
    case Used = 'used';
    case Expired = 'expired';
    case Suspended = 'suspended';
}
