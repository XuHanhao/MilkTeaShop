<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sort_order',
        'image',
        'description',
        'base_price',
        'stock',
        'status',
        'options',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'options' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function optionValues(): HasMany
    {
        return $this->hasMany(ProductOption::class);
    }
}

