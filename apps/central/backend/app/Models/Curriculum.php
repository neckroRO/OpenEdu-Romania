<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'country_code', 'status'])]
class Curriculum extends Model
{
    protected $table = 'curricula';

    public function versions(): HasMany
    {
        return $this->hasMany(CurriculumVersion::class);
    }
}
