<?php

namespace App\Enums;

enum CurriculumProposalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Merged = 'merged';
}
