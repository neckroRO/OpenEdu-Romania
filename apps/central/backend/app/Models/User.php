<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public function lessonVersionReviews(): HasMany
    {
        return $this->hasMany(
            LessonVersionReview::class,
            'reviewer_id'
        );
    }

    public function subjectReputations(): HasMany
    {
        return $this->hasMany(UserSubjectReputation::class);
    }

    public function reputationEvents(): HasMany
    {
        return $this->hasMany(ReputationEvent::class);
    }

    public function curriculumProposals(): HasMany
    {
        return $this->hasMany(
            CurriculumProposal::class,
            'proposed_by'
        );
    }

    public function curriculumProposalReviews(): HasMany
    {
        return $this->hasMany(
            CurriculumProposal::class,
            'reviewed_by'
        );
    }

    public function curriculumAliases(): HasMany
    {
        return $this->hasMany(
            CurriculumAlias::class,
            'created_by'
        );
    }

    public function curriculumAliasApprovals(): HasMany
    {
        return $this->hasMany(
            CurriculumAlias::class,
            'approved_by'
        );
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }
}
