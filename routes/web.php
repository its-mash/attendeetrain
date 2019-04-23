<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::post('addimage','AttendeeController@addImage');
Route::post('createsection','SectionController@createSection');
Route::post('addstudents','SectionController@addStudents');
Route::post('identify','SectionController@recognize');
Route::get('attendance/{courseCode}/{section}','AttendanceController@getQRcode');
Route::get('attendee/{matricno}/{filename}', function ($matricno, $filename)
{
    // im not 100% sure about the $path thingy, you need to fiddle with this one around.
    $path = storage_path(). '/app/attendee/'.$matricno.'/'. $filename;

    if(!File::exists($path)) abort(404);

    $file = File::get($path);
    $type = File::mimeType($path);

    $response = Response::make($file, 200);
    $response->header("Content-Type", $type);

    return $response;
});
