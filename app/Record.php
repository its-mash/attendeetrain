<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Record extends Model
{
  public function attendee(){
    return $this->belongsTo(Attendee::class);
  }
  public function getAttendance($courseCode,$section,$date){
    return Record::where('courseCode',$courseCode)->where('section',$section)->where('taken_at',$date)->get();
  }
}
