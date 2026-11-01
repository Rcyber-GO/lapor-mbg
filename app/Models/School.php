<?php

namespace App\Models;

use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'level', 'address', 'district'])]
class School extends Model
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory;

    public const LEVELS = ['SD', 'SMP', 'SMA', 'SMK'];

    public function distributions(): HasMany
    {
        return $this->hasMany(Distribution::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}
