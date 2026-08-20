<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkpTendik extends Model
{
    use HasFactory;
    protected $table = 'skp_tendik';
    protected $fillable = ['periode_skp_id', 'user_id', 'skp_header_id', 'role_name', 'tupoksi'];

    public function user() { return $this->belongsTo(User::class); }
    public function periodeSkp() { return $this->belongsTo(PeriodeSkp::class); }
    public function header() { return $this->belongsTo(SkpHeader::class, 'skp_header_id'); }


    public function details() {
        return $this->hasMany(SkpTendikDetail::class, 'skp_tendik_id');
    }

    public function penilaian()
    {
        return $this->hasOne(PenilaianSkpTendik::class, 'skp_tendik_id');
    }
}
