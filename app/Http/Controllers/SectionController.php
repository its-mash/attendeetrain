<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Section;
use App\Attendee;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Storage;

use App\Attendance;
class SectionController extends Controller
{
    private $uriBase;
    private $ocpApimSubscriptionKey;
    private $headers;


    function __construct() {
        $this->ocpApimSubscriptionKey = '71edb9d36a6b47bcae9e38fb83239404';
        
        $this->uriBase = 'https://australiaeast.api.cognitive.microsoft.com/face/v1.0/persongroups/';

        // This sample uses the PHP5 HTTP_Request2 package
        // (https://pear.php.net/package/HTTP_Request2).


        $this->headers = array(
            'Content-Type' => 'application/json',
            'Ocp-Apim-Subscription-Key' => $this->ocpApimSubscriptionKey
        );
        
        
    }
    
    public function createSection(Request $r){
        $courseCode=str_replace(' ', '', strtolower($r->courseCode));
        $section=$r->section;
        $sc=Section::where('courseCode',$courseCode)->get();
        $s=null;
        if($sc->isEmpty()){
            $s=new Section;
            $s->courseCode=$courseCode;
            $s->courseName=$r->courseName;
            $s->section=$section;
            $s->lecturer=$r->lecturer;
            $s->save();
            $groupId=$courseCode.$section;
            // return $groupId;
            $client = new Client();
            $res = $client->request('PUT',$this->uriBase.$groupId, [
                'json' => ['name'=>$groupId],
                'headers'=>$this->headers
            ]);
            echo $res->getStatusCode();
            echo $res->getBody();

            $br=new Attendance;
            $br->courseCode=$courseCode;
            $br->section=$section;
            $br->save();

            return "created";
        }

        return "exists";
    }


    public function addFace($url, $matricNo){
        $directory='attendee/'.$matricNo;
        return collect(Storage::files($directory))->map(function($file) use ($url,$matricNo){
            // echo Storage::url($file);
            $img_url=asset($file);
            $client = new Client();
            $res = $client->request('POST',$url, [
                'json' => ['url'=>$img_url],
                'headers'=>$this->headers
            ]);
            return $matricNo."=>".$res->getBody();
        });
    }

    public function train($url){
        $client = new Client();
        $res = $client->request('POST',$url, [

            'headers'=>$this->headers
        ]);
        return "train => ".$res->getBody();
    }

    public function addStudents(Request $r){
        $students=$r->students;
        $courseCode=str_replace(' ', '', strtolower($r->courseCode));
        $section=$r->section;
        $secRow=Section::where('courseCode',$courseCode)->where("section",$section)->first();

        $url=$this->uriBase.$courseCode.$section.'/persons';
        $tr=array();
        foreach ($students as $matricNo){
            $studentRow=Attendee::where('matricNo',$matricNo)->first();
            if($secRow->attendees()->find($studentRow->id))
                continue;
            
            $client = new Client();
            $res = $client->request('POST',$url, [
                'json' => ['name'=>$studentRow->callName],
                'headers'=>$this->headers
            ]);
            echo $matricNo.'=>'.$res->getBody().'\n';
            $person_id=json_decode($res->getBody())->personId;
            $secRow->attendees()->attach($studentRow,array("person_id"=>$person_id));

            array_push($tr, $this->addFace($url.'/'.$person_id.'/persistedFaces',$matricNo));
        }
        array_push($tr,$this->train($this->uriBase.$courseCode.$section.'/train'));
        return var_dump($tr);
    }
    public function recognize(Request $r){
        $url="https://australiaeast.api.cognitive.microsoft.com/face/v1.0/";
        $path = 'attendee/test';
        $row=Attendance::where('key',$r->key)->first();
        $courseCode=$row->courseCode;
        $section=$row->section;
        $count=$row->count;

        $data=$r->img;
        return $data;
        // // list($type, $data) = explode(';', $data);
        // return $data;
        // list(, $data)      = explode(':', $data);
        $data = base64_decode($data);
        return $data;
        $fileName=($count+1).'.png';
        if(Storage::disk('local')->put($path.'/'.$fileName, $data)){
            $img_url=asset($path."/".$fileName);

            $client = new Client();
            $res = $client->request('POST',$url."detect", [
                'json' => ['url'=>$img_url],
                'headers'=>$this->headers
            ]);


            $tr=array();
            // return $res->getBody();
            array_push($tr,"detect => ".$res->getBody());

            $data=json_decode($res->getBody());
            $faceIds=array_map(function($rr){return $rr->faceId;},$data);
            $client = new Client();
            $res = $client->request('POST',$url."identify", [
                'json' => ["personGroupId"=>"csc77771","faceIds"=>$faceIds],
                'headers'=>$this->headers
            ]);
            array_push($tr,"identify => ".$res->getBody());   
            return $res->getBody();
        }
        else{
            return "couldn't store";
        }
 

        
    }
}
