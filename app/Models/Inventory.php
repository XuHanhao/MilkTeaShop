<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_name',
        'unit',
        'current_qty',
        'threshold_qty',
    ];

    protected $casts = [
        'current_qty' => 'decimal:2',
        'threshold_qty' => 'decimal:2',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(InventoryLog::class);
    }
}

