<?php
namespace App\Services;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class DirectoryVerification
{
    public static function prepare(Model $record,array $data): array
    {
        Gate::authorize($record->exists?'update':'create',$record->exists?$record:$record::class);
        $fields=$record instanceof \App\Models\Country
            ? ['code','name_ar','name_en','emergency_information_ar','emergency_information_en','reporting_instructions_ar','reporting_instructions_en']
            : ['country_id','name_ar','name_en','official_url','phone','instructions_ar'];
        $changed=$record->exists && collect($fields)->contains(fn($field)=>array_key_exists($field,$data) && (string)$record->$field!==(string)$data[$field]);
        if ($record->verification_status==='verified' && $changed) $data['verification_status']='in_review';
        $verified=($data['verification_status']??$record->verification_status)==='verified';
        $data['verified_by']=$verified?auth()->id():null;
        $data['last_verified_at']=$verified?now()->toDateString():null;
        if (!$verified) $data['is_published']=false;
        return $data;
    }
}
