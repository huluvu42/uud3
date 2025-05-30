<?php

// app/Models/VoucherPurchase.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherPurchase extends Model
{
    protected $fillable = ['amount', 'day', 'purchase_date', 'stage_id', 'user_id'];

    protected $casts = [
        'amount' => 'decimal:1',
        'day' => 'integer',
        'purchase_date' => 'date',
    ];

    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}