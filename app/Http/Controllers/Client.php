<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Client extends Controller
{

    public function getClienByCIN($cin,$self_cin = "")
    {
        return \App\Client::where([
            ["cin","=",$cin],
            ["cin","!=",$self_cin]
        ])->get();
    }

    public function getClientById($id)
    {
         return \App\Client::with([
             "agences:agence_id,nom",
             "credits",
             "credits.user:id,name",
             "credits.agence:id,nom",
             "ventes",
             "ventes.user:id,name",
             "ventes.agence:id,nom",
        ])->where("id",$id)->get();
    }
    public function getClients(){
        $idsArray = DB::table('agence_client')->select('client_id')->where('agence_id',Auth::user()->agence_id);
        if(request()->has('keyword')){
            $keyword = request()->get("keyword");
            $clients = \App\Client::with(['agences:agence_id,nom'])
                                ->where(function ($query) use($idsArray){
                                    $query->whereIn('id', $idsArray);
                                })->where(function ($query) use($keyword) {
                                $query->where('nom','like','%'.$keyword.'%')
                                      ->orWhere('telephone','like','%'.$keyword.'%')
                                      ->orWhere('cin','like','%'.$keyword.'%');
                    })->get();
            foreach ($clients as $client) {
                $reste = \App\Credit::where('client_id', $client->id)
                                    ->where('agence_id', Auth::user()->agence_id)
                                    ->where('statut','N')
                                    ->sum('reste');
                $client['reste'] = $reste;
            }

            return $clients;
        }
	    $clients = \App\Client::with(['agences:agence_id,nom'])->whereIn('id', $idsArray)->get();
        foreach ($clients as $client) {
            $reste = \App\Credit::where('client_id', $client->id)
                                ->where('agence_id', Auth::user()->agence_id)
                                ->where('statut','N')
                                ->sum('reste');
            $client['reste'] = $reste;
        }

        return $clients;
    }

    public function postClient(Request $request){
        $cin = $request->input('cin');
        $client = $this->getClienByCIN($cin); 

        if(count($client) != 0){
            return response()->json(array(
                    'code'      =>  403,
                    'message'   =>  'CIN déjà existant'
                ), 403); 
        }
        $newClient = new  \App\Client;
        $newClient->nom = $request->input('nom');
        $newClient->cin = $request->input('cin');
        $newClient->telephone = $request->input('telephone');
        $newClient->credit = $request->input('credit') ? $request->input('credit') : 0;
        $agences = $request->input('agences');
        $newClient->save();
        $ids = [];
        foreach ($agences as $a) {
            $ids[] = $a["id"];
        }
        $newClient->agences()->attach($ids);
        return $newClient;
    }

    public function deleteClient($id){
        $client = \App\Client::find($id);
        if($client){
            $client->agences()->detach();
            if($client->delete()){
                return response()->json(array(
                    'code'      =>  200,
                    'message'   =>  "Le client a été supprimé"
                ), 200);  
                 
            }else
                return response()->json(array(
                    'code'      =>  404,
                    'message'   =>  'Le client n\'existe pas.'
                ), 404); 
            
        }
         return response()->json(array(
                    'code'      =>  404,
                    'message'   =>  'Le client n\'existe pas.'
                ), 404); 
    }

    public function getClient($id){
        if(!\Auth::check()){
            //If they are not, we forcefully login the user with id=1
           return 'il faut se connecter';
        }

        return \App\Client::find($id);
    }

     public function updateClient(Request $request){
        $cin = $request->input('cin');
        
        $newClient = \App\Client::find($request->input('id'));
        if(!$newClient){
             return response()->json(array(
                    'code'      =>  403,
                    'message'   =>  "le client n'existe pas."
                ), 404); 
        }
        $client = $this->getClienByCIN($cin,$newClient->cin);
        if(count($client) != 0){
            return response()->json(array(
                    'code'      =>  403,
                    'message'   =>  'CIN déjà existant'
                ), 403); 
        }
        
        $newClient->nom = $request->input('nom');
        $newClient->cin = $cin;
        $newClient->telephone = $request->input('telephone');
        $newClient->credit = $request->input('credit') ? $request->input('credit') : 0;
        $newClient->points = $request->input('points') ? $request->input('points') : 0;
        $ids = [];
        $agences = $request->input('agences');
        foreach ($agences as $a) {
            $ids[] = $a["id"];
        }

        $newClient->save();
        $newClient->agences()->sync($ids);
        return $newClient;
    }
}
