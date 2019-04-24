<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Attendance;
use Illuminate\Support\Str;
use View;

class AttendanceController extends Controller
{
    public function getQRcode(Request $rq,$courseCode,$section){
        $rr=Attendance::where('courseCode',$courseCode)->where('section',$section)->get();
        if(!$rr->isEmpty()){
            $r=$rr->get(0);
            $tkey=$courseCode.(Str::random(32)).$section;
            $r->key=$tkey;
            $r->count=0;
            $r->save();
    
            $data=array(
                "courseCode"=>$courseCode,
                "section"=>$section,
                "src"=>"https://chart.googleapis.com/chart?cht=qr&chs=500x500&chl=".urlencode($tkey)
            );
            // echo $data['src'];
            return View::make("attendance")->with("data",$data);
        }
        else
            return "course code not found";


    }
    public function verifyQR(Request $req){
        $rr=Attendance::where('key',$req->key)->get();
        if(!$rr->isEmpty()){
            $r=$rr->get(0);
            $r->count=$r->count+1;
            $r->save();
            return "valid";
        }
        else
            return "invalid";

    }
}
