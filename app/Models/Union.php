<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Union extends Model
{
    use SoftDeletes;

    protected $fillable = ['upazila_id', 'name_en', 'name_bn', 'url'];

    public function upazila(): BelongsTo
    {
        return $this->belongsTo(Upazila::class);
    }
}
