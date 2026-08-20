<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RabAlokasi extends Model
{
    protected $table = 'rab_alokasi';
    protected $fillable = ['master_rab_id', 'prodi', 'nominal'];

    public function rab()
    {
        return $this->belongsTo(MasterRab::class, 'master_rab_id');
    }
}
