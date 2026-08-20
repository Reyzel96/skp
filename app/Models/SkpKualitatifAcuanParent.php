<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkpKualitatifAcuanParent extends Model
{
    protected $table = 'skp_kualitatif_acuan_parents';
    protected $guarded = ['id'];

    public function children()
    {
        return $this->hasMany(SkpKualitatifAcuanChild::class, 'parent_id')->orderBy('urutan');
    }
}
