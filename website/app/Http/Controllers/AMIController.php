<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Validator;
use App\User;
use Auth;
use App\Models\Ami;

class AMIController extends Controller
{

      public function __construct()
    {
         $this->middleware('auth');
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
       $data['amis'] =  Ami::orderBy('title','desc')->whereNull('deleted_at')->paginate('10');
      return view('ami.index',$data);
    }


   public function create(Request $request)
    {
      return view('ami.create');
    }

       public function edit($id)
    {
      $data['ami'] = Ami::find($id);
      return view('ami.show',$data);
    }

    public function store(Request $request){

      if(auth::user()->role_id != 1){
        return redirect()->route('home');
      }

      $rules = [
          'name'     => 'required|string|unique:amis,title,null,id,deleted_at,NULL',
          'ami_id'     => 'required|string|unique:amis,value,null,id,deleted_at,NULL'
       ];
       
      $request->validate($rules);

       $ami = new Ami;
       $ami->title    = $request->name;
       $ami->value    = $request->ami_id;
       if($ami->save()){
         return redirect()->route('index.ami')->with('status',true)->with('message','Successfully created ami');
       }
         return redirect()->route('index.ami')->with('status',false)->with('message','Failed to create ami');
    }

    public function update(Request $request,$id){

      if(auth::user()->role_id != 1){
        return redirect()->route('home');
      }

      $rules = [
          'name'     => 'required|string|unique:amis,title,'.$id.',id,deleted_at,NULL',
          'ami_id'     => 'required|string|unique:amis,value,'.$id.',id,deleted_at,NULL'
       ];
       
      $request->validate($rules);

       $ami = Ami::find($id);
       $ami->title    = $request->name;
       $ami->value    = $request->ami_id;
       if($ami->update()){
         return redirect()->route('index.ami')->with('status',true)->with('message','Successfully updated ami');
       }
         return redirect()->route('index.ami')->with('status',false)->with('message','Failed to update ami');
    }

     public function destroy(Request $request){

      if(auth::user()->role_id != 1){
        return redirect()->route('home');
      }

      $id = $request->id ?? NULL;

       $ami = Ami::find($id);
       $ami->deleted_at    = date('Y-m-d H:i:s');
       if($ami->update()){
         return redirect()->route('index.ami')->with('status',true)->with('message','Successfully deleted ami');
       }
         return redirect()->route('index.ami')->with('status',false)->with('message','Failed to delete ami');
    }

}
