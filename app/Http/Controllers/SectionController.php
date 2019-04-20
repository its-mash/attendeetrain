<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Section;
use App\Attendee;
class SectionController extends Controller
{
    //
    public function createSection(Request $r){
        $sc=Section::where('courseCode',$r->courseCode)->get();
        $s=null;
        if($sc->isEmpty()){
            $s=new Section;
            $s->courseCode=$r->courseCode;
            $s->courseName=$r->courseName;
            $s->section=$r->section;
            $s->lecturer=$r->lecturer;
            $s->save();
            return "created";
        }
        return "exists";
    }
    public function addStudents(Request $r){
        $students=$r->students;
        $courseCode=$r->courseCode;
        $section=$r->section;
        $secRow=Section::where('courseCode',$courseCode)->where("section",$section)->first();
        foreach ($students as $matricNo){
            $studentRow=Attendee::where('matricNo',$matricNo)->first();
            if($secRow->attendees()->find($studentRow->id))
                continue;
            $secRow->attendees()->attach($studentRow);
        }
        return "success";
    }
}
