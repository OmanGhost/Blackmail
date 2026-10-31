<?php
namespace App\Http\Controllers;
use App\Models\Event;
use Illuminate\Http\Request;
class EventController extends Controller
{
    public function index(Request $request)
    {
        $past=$request->query('period')==='past';
        $events=Event::public()->when($past,fn($q)=>$q->where('ends_at','<',now()),fn($q)=>$q->where('ends_at','>=',now()))
            ->when(in_array($request->query('type'),array_keys(Event::types()),true),fn($q)=>$q->where('event_type',$request->query('type')))
            ->orderBy('starts_at',$past?'desc':'asc')->paginate(9)->withQueryString();
        return view('events.index',compact('events','past'));
    }
    public function show(Event $event)
    {
        abort_unless(in_array($event->status,['published','cancelled']),404);
        return view('events.show',compact('event'));
    }
}
