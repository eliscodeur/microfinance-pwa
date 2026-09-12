<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportHistory extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'module', 'type_export', 'filename', 'filters_used'];

    protected $casts = [
        'filters_used' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}