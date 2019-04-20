<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    //
    public function attendees(){
        return $this->belongsToMany(Attendee::class);
    }
}
