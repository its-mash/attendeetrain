<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Section;
use App\Attendee;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Storage;
use Carbon\Carbon;
use App\Attendance;
use App\Record;
use DB;
use Illuminate\Support\Str;
class SectionController extends Controller
{
    private $uriBase;
    private $ocpApimSubscriptionKey;
    private $headers;


    function __construct() {
        $this->ocpApimSubscriptionKey = '4a42b67c93404e6ab1f3e63c1b3238e8';
        
        $this->uriBase = 'https://murad01.cognitiveservices.azure.com/face/v1.0/persongroups/';

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
        $sc=Section::where('courseCode',$courseCode)->where('section',$section)->get();
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
            echo Storage::url($file);
            $img_url=asset("store/".$file);
            $client = new Client();
            $res = $client->request('POST',$url, [
                'json' => ['url'=>$img_url],
                'headers'=>$this->headers
            ]);
            echo (Carbon::now()->toDateTimeString())." Added ".$file.'<br>';
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
        
        if($secRow==null) return "invalid section";

        $url=$this->uriBase.$courseCode.$section.'/persons';
        $tr=array();
        foreach ($students as $matricNo){
            echo (Carbon::now()->toDateTimeString())." Adding ".$matricNo.'<br>';
            $studentRow=Attendee::where('matricNo',$matricNo)->first();
            if($secRow->attendees->isNotEmpty() && $secRow->attendees()->find($studentRow->id))
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
            echo (Carbon::now()->toDateTimeString())." Added".$matricNo.'<br>';
        }
        echo (Carbon::now()->toDateTimeString())." Training group".$courseCode.$section.'<br>';
        array_push($tr,$this->train($this->uriBase.$courseCode.$section.'/train'));
        echo (Carbon::now()->toDateTimeString())." Training finish".$courseCode.$section.'<br>';
        return var_dump($tr);
    }
    public function recognize(Request $r){
        $row=Attendance::where('key',$r->key)->first();
        $courseCode=$row->courseCode;
        $section=$row->section;
        $count=$row->count;

        $url="https://murad01.cognitiveservices.azure.com/face/v1.0/";
        $path = 'test/'.$courseCode.$section."/".(Carbon::now()->toDateString());

        $data=$r->img;
        // return $data;
        // // list($type, $data) = explode(';', $data);
        // return $data;
        // list(, $data)      = explode(':', $data);
        $data = base64_decode($data);
        // return $data;
        // $fileName=(Str::random(32)).'.png';
        $fileName=(str_replace(' ', '', Carbon::now()->toDateTimeString())).'.png';

        if(Storage::disk('local')->put($path.'/'.$fileName, $data)){
            $img_url=asset("store/".$path."/".$fileName);

            $client = new Client();
            $res = $client->request('POST',$url."detect", [
                'json' => ['url'=>$img_url],
                'headers'=>$this->headers
            ]);


            $tr=array();
            // return $res->getBody();
            // array_push($tr,"detect => ".$res->getBody());

            $data=json_decode($res->getBody());
            $data_identify=json_decode($res->getBody());
            if(empty($data_identify))
                return $res->getBody();
            $faceIds=array_map(function($rr){return $rr->faceId;},$data);
            $faceRectangles=array_map(function($rr){return $rr->faceRectangle;},$data);
            $client = new Client();
            $res = $client->request('POST',$url."identify", [
                'json' => ["personGroupId"=>$courseCode.$section,"faceIds"=>$faceIds,"maxNumOfCandidatesReturned"=> 1],
                'headers'=>$this->headers
            ]);
            array_push($tr,"identify => ".$res->getBody());   
            
            $data=json_decode($res->getBody());
            if(!empty($data)){
                $personIds=array_map(function($r){return empty($r->candidates[0])? "undefined":$r->candidates[0]->personId;},$data);
                foreach($personIds as $key=>$personId){
                    $callName="undefined";
                    $id=-1;
                    if($personId!='undefined'){
                        $attendee_id=DB::table('attendee_section')->where('person_id',$personId)->first()->attendee_id;
                        $callName=Attendee::find($attendee_id)->callName;
                        $exx=Record::where('courseCode',$courseCode)->where('section',$section)->where('taken_at',Carbon::today()->isoFormat("DD-MM-YYYY"))->where('attendee_id',$attendee_id)->get();
                        if($exx->isEmpty())
                        {
                            $record=new Record;
                            $record->courseCode=$courseCode;
                            $record->section=$section;
                            $record->attendee_id=$attendee_id;
                            $record->taken_at=Carbon::today()->isoFormat("DD-MM-YYYY");
                            $record->save();
                            $id=$record->id;
                        }
                        else{
                            $id=$exx->get(0)->id;
                        }

                    }
                    $faceRectangles[$key]->callName=$callName;
                    $faceRectangles[$key]->fileName=$fileName;
                    $faceRectangles[$key]->id=$id;

                }
            }
            return json_encode($faceRectangles);
        }
        else{
            return "couldn't store";
        }
 

        
    }
    
    
    public function deleteGroups(Request $r){
        $client = new Client();
        $res = $client->request('GET',$this->uriBase, [
            'headers'=>$this->headers
        ]);
        
        $groups=json_decode($res->getBody());
        

        foreach($groups as $group){
            $client = new Client();
            $res = $client->request('DELETE',$this->uriBase.$group->personGroupId, [
                'headers'=>$this->headers
            ]);
            echo $group->personGroupId." ".$res->getBody();
        }
        
    }
}


