<?php

namespace App\Enums;

/**
 * How the racepack desk entered a registration: by scanning the participant's QR with the camera,
 * or by typing an ID when no camera is available.
 */
enum QrScanSource: string
{
    case Camera = 'camera';
    case Manual = 'manual';
}
