<?php

namespace App\Models;

use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;

class Business extends Model
{
    /** @use HasFactory<BusinessFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'owner_name',
        'phone',
        'address',
        'is_active',
        'deactivated_at',
        'deactivated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deactivated_at' => 'datetime',
    ];

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logOnly(['name', 'is_active', 'deactivated_at'])
            ->logOnlyDirty()
            ->useLogName('business');
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function deactivatedBy()
    {
        return $this->belongsTo(User::class, 'deactivated_by');
    }

    public function ownerUser()
    {
        return $this->hasOne(User::class, 'business_id')
            ->whereHas('roles', fn ($q) => $q->where('name', 'Owner'));
    }
}
