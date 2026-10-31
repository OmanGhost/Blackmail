<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DirectoryRequest extends Model
{
    protected $guarded=[];
    protected $casts=['contact_name'=>'encrypted','organization'=>'encrypted','job_title'=>'encrypted','email'=>'encrypted','phone'=>'encrypted','details'=>'encrypted','review_notes'=>'encrypted','consented_at'=>'datetime','reviewed_at'=>'datetime'];
    public function country() { return $this->belongsTo(Country::class); }
    public static function statuses(): array { return ['pending'=>__('directory.pending'),'in_review'=>__('directory.in_review'),'completed'=>__('directory.completed'),'rejected'=>__('directory.rejected')]; }
}
