<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function getServicesByUserId()
    {
        if(auth()->user()->role == "user"){
            return \App\Service::with([
                'user' => function($q){
                    $q -> select("id","name");
                },
                ])->where('user_id','=',Auth::user()->id)
                ->get();
        }

        return \App\Service::with([
                'user' => function($q){
                    $q -> select("id","name");
                },
                ])
                ->get();        
    }

    public function getService($id){
        return \App\Service::with([
                'user' => function($q){
                    $q -> select("id","name");
                },
                ])->where('id','=',$id)
                ->get();    
    }

    public function postService(Request $request){
        $service = new  \App\Service;
        $service->user_id = Auth::user()->id;
        $service->agence_id = Auth::user()->agence_id;
        $service->description = $request->input('description');
        $service->montant = $request->input('montant');
        $service->save();
        
        $user = \App\User::find(Auth::user()->id);
        $p_user = $user->p_service/100;
        $commission = new \App\Commission;
        $commission->user_id = Auth::user()->id;
        $commission->service_id = $service->id;
        $commission->commission = $service->montant * $p_user;
        $commission->save();
        return $service;
    }

    public function updateService(Request $request){
        $service = \App\Service::find($request->input('id'));
        $service->description = $request->input('description');
        $service->montant = $request->input('montant');
        $service->save();

        $user = \App\User::find(Auth::user()->id);
        $p_user = $user->p_service/100;
        $commission = \App\Commission::where('service_id',$service->id);
        $commission->commission = $service->montant * $p_user;
        $commission->save();
        return $service;
    }

    public function deleteService($id){
        $service = \App\Service::find($id);
        $commission = \App\Commission::where('service_id',$service->id);
        $commission->delete();
        $service->delete(); 
    } 
}
