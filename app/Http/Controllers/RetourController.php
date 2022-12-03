<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RetourController extends Controller
{
    public function getRetours()
    {
    	$rawRetours = \App\Retour::with([
            'agence',

    		'produits' => function($query){
    			$query -> select("nom");
    		},
            'client' => function($q){
                $q -> select("id","nom");
            },
            'user' => function($q){
                $q -> select("id","name");
            },
    	])->get();

    	$retours = [];

    	foreach ($rawRetours as $retour) {
    		$produits = [];
    		foreach ($retour['produits'] as $produit) {
    			$produits[] = [
    				"nom" => $produit['nom'],
                    "id" => $produit['id'],
    				"quantite" => $produit['pivot']['quantite_vente_produit'],
    				"prix" => $produit['pivot']['prix_vente'],
                    "remise" => $produit['pivot']['remise'],
                    "type" => $produit['pivot']['type'],
    			];
    		}
    		$retours[] = [
    			"id" => $retour["id"],
    			"montant" => $retour["montant"],
                "client" => $retour["client"],
                "user" => $retour["user"],
    			"date" => $retour['created_at'],
    			"agence" => $retour["agence"],  
                "type_vente" => $retour["type_vente"],              
    			"produits" => $produits
    		];
    		
    	}

    	return $retours;

    }

    public function deleteRetour($id){
        $retour = \App\Retour::find($id);
        if($retour){ 
            $produits = DB::table('retour_produit')->where('retour_id',$id)->get();
            $client = \App\Client::find($retour->client_id);
            foreach ($produits as $a) {
                $produit = \App\Produit::find($a->produit_id); 
                //stock
                if($a->type == "nouveau"){
                    $produit->quantite -= $a->quantite_vente_produit;
                }else{
                    $produit->quantite_casio = $produit->quantite_casio - $a->quantite_vente_produit;     
                    $q = $a->quantite_vente_produit;
                    $achats_produits = DB::table('achat_produit')->where('produit_id','=',$a->id)
                                                                 ->where('type','=','casio')
                                                                 ->where('quantite_restante','>',-1)
                                                                 ->orderBy('created_at', 'desc')->get();
                    foreach ($achats_produits as $pa)
                    {
                        if($q > 0 && $pa->quantite_restante > 0){
                            if($pa->quantite_restante < $q){
                                    $q = $q - $pa->quantite_restante;                                            
                                    DB::table('achat_produit')->where('id','=',$pa->id)
                                                              ->update(['quantite_restante'=> 0]); 
                            }else{
                                    DB::table('achat_produit')->where('id','=',$pa->id)
                                                              ->update(['quantite_restante'=>$pa->quantite_restante-$q]); 
                                    $q = 0;
                            }
                        }
                    }
                }
                //client                                  
                if($retour->type_vente == 0)
                {
                    $client->points += $produit->points_d*$a->quantite_vente_produit;
                }
                else
                {
                    $client->points += $produit->points_g*$a->quantite_vente_produit;
                } 
                $produit->save();             
            }
            $client->save();     
            $retour->produits()->detach(); 
            DB::table('commissions')->where('retour_id','=',$id)
                                    ->delete(); 
            if($retour->delete()){                             
                return response()->json(array(
                    'code'      =>  200,
                    'message'   =>  "Le retour a été supprimé"
                ), 200);  
                 
            }else
                return response()->json(array(
                    'code'      =>  404,
                    'message'   =>  'Le retour n\'existe pas.'
                ), 404); 
            
        }
         return response()->json(array(
                    'code'      =>  404,
                    'message'   =>  'Le retour n\'existe pas.'
                ), 404); 
    }


    public function postRetour(Request $request)
    {
    	try{
            $retour = new  \App\Retour;
            $retour_montant =0;
            $retour->client_id = $request->input('client_id');
            $retour->user_id = Auth::user()->id;
            $retour->agence_id = auth()->user()->agence_id;
            $produits = $request->input('produits');
            $client = \App\Client::find($request->input('client_id'));
            if($request->input('typeVente') == "D"){
                $retour->type_vente = 0; //detail
            }else{
                $retour->type_vente = 1; //gros
            }
            foreach ($produits as $a) {
                $produit = \App\Produit::find($a["id"]);                                   
                if($request->input('typeVente') == "D")
                {
                    $client->points -= $produit->points_d*$a["quantite"];
                }
                else
                {
                    $client->points -= $produit->points_g*$a["quantite"];
                }

                $retour_montant += $a["quantite"]*$a["price"]*(1-$a["remise"]/100);                 
            }
            $retour->montant = $retour_montant;
            $retour->save();
            $client->save();
            foreach ($produits as $a)
            {
                $produit = \App\Produit::find($a["id"]); 
                if($a['casio'] == "nouveau"){
                    if($produit->prix_achat < $a["price"]*(1-$a["remise"]/100)){   
                        $commission = new \App\Commission;
                        $commission->retour_id = $retour->id;
                        $commission->user_id = Auth::user()->id;
                        $commission->produit_id = $a["id"];
                        $commission->quantite = $a["quantite"];                 
                        if($request->input('typeVente') == "D"){
                            $commission->commission = -($a["quantite"]*($a["price"]*(1-$a["remise"]/100)
                                                  - $produit->prix_achat)*$produit->pourcentage_d/100);
                        }else{
                            $commission->commission = -($a["quantite"]*($a["price"]*(1-$a["remise"]/100)
                                                  - $produit->prix_achat)*$produit->pourcentage_g/100);
                        }
                        $commission->save();
                    }                        
                    $produit->quantite = $produit->quantite + $a["quantite"];     
                    $produit->save();
                    $retour->produits()->attach($a["id"],[
                    "quantite_vente_produit" => $a["quantite"],
                    "prix_vente_recommande" => $a["price_recommande"],
                    "prix_vente" => $a["price"],
                    "remise" => $a["remise"],
                    "type" => "nouveau",
                    ]);
                }
                else
                {
                    $q = $a["quantite"];
                    $reste = 0;
                    $achats_produits = DB::table('achat_produit')->where('produit_id','=',$a["id"])
                                                                 ->where('type','=','casio')
                                                                 ->where('quantite_restante','>',-1)
                                                                 ->orderBy('created_at', 'desc')->get();
                    foreach ($achats_produits as $pa)
                    {
                        if($q != 0){
                            if($pa->quantite_restante < $pa->quantite_achat_produit){
                                if($pa->quantite_restante + $q > $pa->quantite_achat_produit){
                                    $reste = $pa->quantite_restante + $q - $pa->quantite_achat_produit;
                                    $q = $q - $reste;                                            
                                    DB::table('achat_produit')->where('id','=',$pa->id)
                                                              ->update(['quantite_restante'=>$pa->quantite_restante+$q]); 
                                    $q = $reste;
                                }else{
                                    DB::table('achat_produit')->where('id','=',$pa->id)
                                                              ->update(['quantite_restante'=>$pa->quantite_restante+$q]); 
                                    $q = 0;
                                }
                            }
                        }
                    }
                    $q = $a["quantite"];
                    $reste = 0;                                            
                    if($produit->prix_achat_casio < $a["price"]*(1-$a["remise"]/100))
                    {
                        $user = \App\User::find(Auth::user()->id);    
                        $commission = new \App\Commission;
                        $commission->retour_id = $retour->id;
                        $commission->user_id = Auth::user()->id;
                        $commission->produit_id = $a["id"];
                        $commission->quantite = $a["quantite"];                  
                        $commission->commission = -($a["quantite"]*($a["price"]*(1-$a["remise"]/100)
                                                  - $produit->prix_achat_casio)*$user->p_casio_vente/100);
                        $commission->save();                      
                            foreach ($achats_produits as $pa)
                            {
                                if($q != 0){
                                    if($pa->quantite_restante < $pa->quantite_achat_produit)
                                    {
                                        if($pa->quantite_restante + $q >= $pa->quantite_achat_produit){
                                            $reste = $pa->quantite_restante + $q - $pa->quantite_achat_produit;
                                            $q = $q - $reste;                                           
                                            $achat = \App\Achat::find($pa->achat_id);
                                            $u =  \App\User::find($achat->user_id);
                                            $commission = new \App\Commission;
                                            $commission->vente_id = 0;
                                            $commission->retour_id = $retour->id;
                                            $commission->user_id = $achat->user_id;
                                            $commission->produit_id = $a["id"]; 
                                            $commission->quantite = $q;                 
                                            $commission->commission = -($q*($a["price"]*(1-$a["remise"]/100)
                                                                - $produit->prix_achat_casio)*$u->p_casio_achat/100);
                                            $commission->save(); 
                                            $q = $reste;
                                        }else{
                                            $achat = \App\Achat::find($pa->achat_id);
                                            $u =  \App\User::find($achat->user_id);
                                            $commission = new \App\Commission;
                                            $commission->vente_id = 0;
                                            $commission->retour_id = $retour->id;
                                            $commission->user_id = $achat->user_id;
                                            $commission->produit_id = $a["id"]; 
                                            $commission->quantite = $q;                 
                                            $commission->commission = -($q*($a["price"]*(1-$a["remise"]/100)
                                                                - $produit->prix_achat_casio)*$u->p_casio_achat/100);
                                            $commission->save(); 
                                            $q = 0;
                                        }
                                    }

                                }
                            }
                    }                        
                    $produit->quantite_casio = $produit->quantite_casio + $a["quantite"];     
                    $produit->save();
                    $retour->produits()->attach($a["id"],[
                    "quantite_vente_produit" => $a["quantite"],
                    "prix_vente_recommande" => $a["price_recommande"],
                    "prix_vente" => $a["price"],
                    "remise" => $a["remise"],
                    "type" => "casio",
                    ]);
                }  
            }
            return $retour;
        }
        catch(Exception $e)
        {
            return response()->json(array(
                    'code'      =>  500,
                    'message'   =>  'Une erreur est survenue'
                ), 500); 
        }
    }

    public function getRetour($id){


        $rawRetours = \App\Retour::with([
            'agence',

            'produits' => function($query){
                $query -> select("nom","produits.id","produits.quantite");
        },
            'client' => function($q){
                $q -> select("id","nom");
        },

        ])->where("id",$id)->get();

        $retours = [];

        foreach ($rawRetours as $retour) {
            $produits = [];
            foreach ($retour['produits'] as $produit) {
                $produits[] = [
                    "nom" => $produit['nom'],
                    "id" => $produit['id'],
                    "quantite" => $produit['pivot']['quantite_vente_produit'],
                    "quantite_produit" => $produit["quantite"],
                    "prix" => $produit['pivot']['prix_vente'],
                    "remise" => $produit['pivot']['remise'],
                ];
            }
            $retours[] = [
                "id" => $retour["id"],
                "montant" => $retour["montant"],
                "client" => $retour["client"],
                "client_id" =>$retour["client"]["id"],
                "date" => $retour['created_at'],
                "agence" => $retour["agence"],
                "type_vente" => $retour["type_vente"],
                "produits" => $produits
            ];           
        }

        return $retours;
    }
    
    //a modifier
    public function updateRetour(Request $request){
        try{
            $retour = \App\Retour::find($request->input('id'));
            $retour->client_id = $request->input('client_id');
            $produits = $request->input('produits');
            $retour->montant = 0;
            $old_retour=  $this->getRetour($request->input('id'));
            $client = \App\Client::find($request->input('client_id'));
            foreach ($produits as $a) {
                $p = \App\Produit::find($a["id"]);
                foreach ($old_retour[0]["produits"] as $pro) {
                    if($pro['id'] == $a["id"]){
                        $p->quantite -= $pro['quantite'];
                        if($retour->type_vente = 0){
                            $client->points += $p->points_d*$pro['quantite'];
                        }else{
                            $client->points += $p->points_g*$pro['quantite'];
                        }                       
                    }
                }

                $retour->montant += $a["quantite"]*$a["price"]*(1-$a["remise"]/100);  
                $p->quantite = $p->quantite + $a["quantite"];
                $retour->produits()->sync([
                    $a["id"]=>[
                    "quantite_vente_produit" => $a["quantite"],
                    "prix_vente_recommande" => $a["price_recommande"],
                    "prix_vente" => $a["price"],
                    "remise" => $a["remise"]]
                ], false);
                $commission = \App\Commission::where([
                                        ['retour_id','=',$request->input('id')],
                                        ['produit_id', '=' ,$a["id"]],
                                        ['vente_id','=', 0]
                                        ])->get();
                if($request->input('typeVente') == "D"){
                    if($commission != null){
                        if($p->prix_achat < $a["price"]*(1-$a["remise"]/100)){
                            $commission->commission = -($a["quantite"]*($a["price"]*(1-$a["remise"]/100)
                                                      - $p->prix_achat)*$p->pourcentage_d/100);
                            $commission->save();
                        }else{
                        $commission->delete();
                        } 
                    }else{
                        if($p->prix_achat < $a["price"]*(1-$a["remise"]/100))
                            {
                            $commission = new \App\Commission;
                            $commission->vente_id = 0;
                            $commission->retour_id = $retour->id;
                            $commission->user_id = $user_id;
                            $commission->produit_id = $a["id"];
                            $commission->commission = -($a["quantite"]*($a["price"]*(1-$a["remise"]/100)
                                                      - $p->prix_achat)*$p->pourcentage_d/100); 
                            $commission->save();
                        }
                    }
                $client->points -= $p->points_d*$a["quantite"];  
                }else{
                    if($commission != null){
                        if($p->prix_achat < $a["price"]*(1-$a["remise"]/100)){
                            $commission->commission = -($a["quantite"]*($a["price"]*(1-$a["remise"]/100)
                                                      - $p->prix_achat)*$p->pourcentage_g/100);
                            $commission->save();
                        }else{
                        $commission->delete();
                        } 
                    }else{
                        if($p->prix_achat < $a["price"]*(1-$a["remise"]/100))
                            {
                            $commission = new \App\Commission;
                            $commission->vente_id = 0;
                            $commission->retour_id = $retour->id;
                            $commission->user_id = $user_id;
                            $commission->produit_id = $a["id"];
                            $commission->commission = -($a["quantite"]*($a["price"]*(1-$a["remise"]/100)
                                                      - $p->prix_achat)*$p->pourcentage_g/100); 
                            $commission->save();
                        }
                    } 
                $client->points -= $p->points_g*$a["quantite"];                   
                }
                $p->save(); 
            }
            if($request->input('typeVente') == "D"){
                $retour->type_vente = 0;
            }else{
                $retour->type_vente = 1;
            }
            $client->save();
            $retour->save();
            return $retour;
        }catch(Exception $e){
            return response()->json(array(
                    'code'      =>  500,
                    'message'   =>  'Une erreur est survenue'
                ), 500); 
        }
    }
}
