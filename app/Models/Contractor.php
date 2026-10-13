<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contractor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'contact_person',
        'phone',
        'email',
    ];

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }
}
