<?php

namespace App\Enums;

enum CredentialMode: string
{
    case Unique = 'unique';
    case Separate = 'separate';
}
