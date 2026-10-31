<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Event extends Model
{
    protected $guarded=[];
    protected $casts=['starts_at'=>'datetime','ends_at'=>'datetime','agenda'=>'array'];
    public function scopePublic($query) { return $query->whereIn('status',['published','cancelled']); }
    public function localized(string $field): ?string { return app()->getLocale()==='en' ? ($this->{$field.'_en'} ?: $this->{$field.'_ar'}) : $this->{$field.'_ar'}; }
    public static function types(): array { return ['workshop'=>__('events.workshop'),'lecture'=>__('events.lecture'),'seminar'=>__('events.seminar')]; }
    public static function modes(): array { return ['onsite'=>__('events.onsite'),'online'=>__('events.online'),'hybrid'=>__('events.hybrid')]; }
    public static function statuses(): array { return ['draft'=>__('events.draft'),'published'=>__('events.published'),'cancelled'=>__('events.cancelled')]; }
}
