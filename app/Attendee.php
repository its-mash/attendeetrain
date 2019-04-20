<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Attendee extends Model
{
    //
    static function getCount($matricNo){
        
        $r= Attendee::where('matricNo',$matricNo)->first();
        if($r)
            return $r->count;
        return 0;
    }
    static function exist($matricNo){
        $r= Attendee::where('matricNo',$matricNo)->get();
        return !$r->isEmpty();
    }
    static function countChange($matricNo, $by){
        $r= Attendee::where('matricNo',$matricNo)->first();
        $r->count+=$by;
        $r->save();
    }

    public function sections(){
        return $this->belongsToMany(Section::class);
    }
}
