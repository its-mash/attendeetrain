<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Record extends Model
{
  public function attendee(){
    return $this->belongsTo(Attendee::class);
  }
}
