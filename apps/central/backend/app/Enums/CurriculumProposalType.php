<?php

namespace App\Enums;

enum CurriculumProposalType: string
{
    case Create = 'create';
    case Update = 'update';
    case Alias = 'alias';
    case MergeCandidate = 'merge_candidate';
}
