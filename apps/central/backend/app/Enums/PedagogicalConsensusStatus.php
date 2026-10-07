<?php

namespace App\Enums;

enum PedagogicalConsensusStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case ChangesRequested = 'changes_requested';
    case Rejected = 'rejected';
}
