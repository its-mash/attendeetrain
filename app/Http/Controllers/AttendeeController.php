<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Attendee;
use Storage;
class AttendeeController extends Controller
{


    function addImage(Request $req){
        $matricNo=$req->matricNo;
        $path = 'attendee/'.$matricNo;
        if(!Attendee::exist($matricNo)){
            $user=new Attendee;
            $user->fullName=$req->fullName;
            $user->callName=$req->callName;
            $user->matricNo=$req->matricNo;
            $user->save();
            
          
            Storage::disk('local')->makeDirectory($path);
        }
        
        $data=$req->img;
        list($type, $data) = explode(';', $data);
        list(, $data)      = explode(',', $data);
        $data = base64_decode($data);

        $count= Attendee::getCount($matricNo);
        $fileName=($count+1).'.png';





        if(Storage::disk('local')->put($path.'/'.$fileName, $data)){
            Attendee::countChange($matricNo,1);
            return "stored";
        }
        
    }
}
