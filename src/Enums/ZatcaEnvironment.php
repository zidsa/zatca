<?php

declare(strict_types=1);

namespace Zid\Zatca\Enums;

enum ZatcaEnvironment
{
    case PRODUCTION;
    case SIMULATION;
    case SANDBOX;
}
