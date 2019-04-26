<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Attendance;
use Carbon\Carbon;
use App\Record;
class RecordController extends Controller
{
    public function record(Request $req){
        $ids=$req->ids;
        $row=Attendance::where('key',$req->key)->first();
        $courseCode=$row->courseCode;
        $section=$row->section;
        $record=Record::where('courseCode',$courseCode)->where('section',$section)->where('taken_at',Carbon::today()->isoFormat("DD-MM-YYYY"))->get();
        foreach($ids as $id){
            if($record->find($id)){
                $attendeee=$record->find($id);
                $attendeee->confirmed=true;
                $attendeee->save();
                
            }
            else
                return "invalid person id";
        }
        return "succesfully recorded";
    }
}
