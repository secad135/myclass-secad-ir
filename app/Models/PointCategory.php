<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointCategory extends Model
{
    protected $fillable = ['name', 'default_points', 'description', 'is_active'];

    protected $casts = [
        'is_active'      => 'boolean',
        'default_points' => 'integer',
    ];

    public function entries()
    {
        return $this->hasMany(PointEntry::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function pointsLabel(): string
    {
        return ($this->default_points > 0 ? '+' : '') . $this->default_points;
    }

    public function color(): string
    {
        return $this->default_points >= 0 ? 'success' : 'danger';
    }
}