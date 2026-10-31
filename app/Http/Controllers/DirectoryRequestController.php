<?php
namespace App\Http\Controllers;
use App\Models\Country;
use App\Models\DirectoryRequest;
use Illuminate\Http\Request;

class DirectoryRequestController extends Controller
{
    public function create(Request $request)
    {
        $request->validate(['country'=>['nullable','integer']]);
        $kind=in_array($request->query('kind'),['addition','correction'])?$request->query('kind'):'addition';
        $country=Country::find($request->query('country'));
        return view('content.directory-request', compact('kind','country')+['countries'=>Country::orderBy('name_ar')->get()]);
    }
    public function store(Request $request)
    {
        $data=$request->validate([
            'kind'=>['required','in:addition,correction'],
            'country_id'=>['nullable','required_if:kind,correction','exists:countries,id'],
            'country_name'=>['required_if:kind,addition','nullable','string','max:150'],
            'contact_name'=>['required','string','max:150'],
            'organization'=>['required_if:kind,addition','nullable','string','max:200'],
            'job_title'=>['required_if:kind,addition','nullable','string','max:150'],
            'email'=>['required','email:rfc','max:255'],
            'phone'=>['nullable','string','max:50'],
            'official_url'=>['required','url:http,https','max:1000'],
            'details'=>['required','string','min:20','max:5000'],
            'consent'=>['accepted'],
        ]);
        $country=Country::find($data['country_id']??null);
        unset($data['consent']);
        $data['country_name']=$country?->name_ar ?: $data['country_name'];
        $record=DirectoryRequest::create($data+['status'=>'pending','consented_at'=>now()]);
        return redirect()->route('directory.requests.create',['kind'=>$data['kind']])->with('directory_received',(string)(200000+$record->id));
    }
}
