<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Str;
use View;
use Storage;
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
                "courseCode"=>strtoupper($courseCode),
                "section"=>$section,
                "src"=>"https://chart.googleapis.com/chart?cht=qr&chs=500x500&chl=".urlencode($tkey),
                "key"=>$tkey
            );
            // echo $data['src'];
            return View::make("attendance")->with("data",$data);
        }
        else
            return "course code not found";


    }
    public function verifyQR(Request $req){
        // return json_encode($req->key);
        $rr=Attendance::where('key',$req->key)->get();
        if(!$rr->isEmpty()){
            $r=$rr->get(0);
            $r->count=($r->count)+1;
            $r->save();
            return "valid";
        }
        else
            return "invalid";

    }
    public function getCount(Request $req){
        $r=Attendance::where('key',$req->key)->first();
        return $r->count;
    }
    public function gallery(Request $req,$courseCode,$section){
        $directory='test/'.$courseCode.$section."/".(Carbon::now()->toDateString());
        $imgs=collect(Storage::files($directory))->map(function($file){
                // echo Storage::url($file);
                $img_url=asset("store/".$file);
                return $img_url;
                });
        $data=array(
            "imgs" =>$imgs,
            "count"=>$imgs->count()   
        );
        return view("gallery",["data"=>$data]);
    }
}
