<?php namespace App\Http\Controllers;

use \App\Group;

use Input;

use Illuminate\Http\Response;
use Illuminate\Http\Request;

use Image;

use File;
use Auth;
use Storage;

class FileController extends Controller {

  /**
  * Display a listing of the resource.
  *
  * @return Response
  */
  public function index($id)
  {
    if ($id)
    {
      $group = Group::findOrFail($id);
      $files = $group->files()->orderBy('updated_at', 'desc')->paginate(20);
      return view ('files.index')
      ->with('files', $files)
      ->with('group', $group)
      ->with('tab', 'files');
    }

  }


  /**
  * Show the form for creating a new resource.
  *
  * @return Response
  */
  public function create()
  {

  }

  /**
  * Store a newly created resource in storage.
  *
  * @return Response
  */
  public function store(Request $request, $id)
  {
    try
    {
      $file = new \App\File;


      // we save it first to get an ID from the database, it will later be used to generate a unique filename.
      $file->save();


      // add group
      $file->group()->associate(Group::findOrFail($id));

      // add user
      if (Auth::check())
      {
        $file->user()->associate(Auth::user());
      }
      else
      {
        abort(401, 'user not logged in TODO');
      }

      // generate filenames and path
      $filepath = '/groups/' . $file->group->id . '/';
      $filename = $file->id . '.' . strtolower($request->file('file')->getClientOriginalExtension());


      // store the file
      Storage::disk('local')->put($filepath. $filename,  File::get($request->file('file')));

      // add path and other infos to the file record on DB
      $file->path = $filepath . $filename;
      $file->name = $request->file('file')->getClientOriginalName();
      $file->original_filename = $request->file('file')->getClientOriginalName();
      $file->mime = $request->file('file')->getClientMimeType();



      // save it again
      $file->save();




      if ($request->ajax())
      {
      return Response::json('success', 200);
    }
    else
      {
        $request->session()->flash('message', 'File was uploaded successfuly');
        return redirect()->back();
      }


    }
    catch (Exception $e)
    {
      return Response::json($e->getMessage(), 400);
    }

}





/**
* Display the specified resource.
*
* @param  int  $id
* @return Response
*/
public function show($group_id, $file_id)
{
  $entry = \App\File::findOrFail($file_id);

  // solution 1 : redirect so the file is served directly by the server
  // explained here : https://laracasts.com/discuss/channels/general-discussion/serving-static-files-like-user-uploads/?page=2
  //return redirect(url($entry->path));


  if (Storage::exists($entry->path))
  {
  // solution 2 : the file is served by laravel. More overhead but more flexibility...

  /*
  header('Content-type: ' . $entry->mime);
  die(Storage::get($entry->path));
  */

  return (new Response(Storage::get($entry->path), 200))
          ->header('Content-Type', $entry->mime);

}
else
{
    abort(404, 'File not found in storage at ' . $entry->path);
}



}

public function thumbnail($group_id, $file_id)
  {
    $entry = \App\File::findOrFail($file_id);

    if ($entry->mime == 'image/jpeg')
    {
    $img = Image::make(storage_path(). '/app/' . $entry->path)->fit(24, 24);
    return $img->response('jpg');
    }
    else
    {
        abort(404);
    }
  }

/**
* Show the form for editing the specified resource.
*
* @param  int  $id
* @return Response
*/
public function edit($id)
{

}

/**
* Update the specified resource in storage.
*
* @param  int  $id
* @return Response
*/
public function update($id)
{

}

/**
* Remove the specified resource from storage.
*
* @param  int  $id
* @return Response
*/
public function destroy($id)
{

}

}

?>
