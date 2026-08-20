<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkpKualitatifAcuanChild extends Model
{
    protected $table = 'skp_kualitatif_acuan_children';
    protected $guarded = ['id'];

    public function parent()
    {
        return $this->belongsTo(SkpKualitatifAcuanParent::class, 'parent_id');
    }
}
