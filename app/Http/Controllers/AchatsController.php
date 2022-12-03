<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AchatsController extends Controller
{
    public function getAllAchats()
    {
        if(auth()->user()->role == "user"){
            $rawAchats = \App\Achat::with([
                'agence',
    
                'produits' => function($query){
                    $query -> select("nom");
            },
                'fournisseur' => function($q){
                    $q -> select("id","name");
            },
                'user' => function($q){
                    $q -> select("id","name");
            }, 
    
            ])
            ->where("agence_id",auth()->user()->agence_id)
            ->orderBy('id', 'desc')
            ->get();
        }else
        {
            $rawAchats = \App\Achat::with([
                'agence',
    
                'produits' => function($query){
                    $query -> select("nom");
            },
                'fournisseur' => function($q){
                    $q -> select("id","name");
            },
                'user' => function($q){
                    $q -> select("id","name");
            },
            ])
            ->orderBy('id', 'desc')
            ->get();
        }

    	$achats = [];

    	foreach ($rawAchats as $achat) {
    		$produits = [];
    		foreach ($achat['produits'] as $produit) {
    			$produits[] = [
    				"nom" => $produit['nom'],
    				"quantite" => $produit['pivot']['quantite_achat_produit'],
    				"prix" => $produit['pivot']['prix_achat'],
                    "remise" => $produit['pivot']['remise'],
                    "type" => $produit['pivot']['type'],
                    "quantite_restante" => $produit['pivot']['quantite_restante']
    			];
    		}
    		$achats[] = [
    			"id" => $achat["id"],
                "user_id" => $achat["user_id"],
    			"montant" => $achat["montant"],
    			"fournisseur" => $achat["fournisseur"]["name"],
                "user" => $achat["user"]["name"],
    			"date" => $achat['created_at']->toDateString(),
    			"agence" => $achat["agence"],
    			"produits" => $produits
    		];
    		
    	}

    	return $achats;
    }


    public function postAchat(Request $request)
    {
    	$achat = new  \App\Achat;
        $achat->fournisseur_id = $request->input('fournisseur_id');
        $achat->user_id = auth()->user()->id;
        $achat->agence_id = auth()->user()->agence_id;
        $produits = $request->input('produits');
        $montant = 0;
        foreach ($produits as $a) {
            $montant += $a["quantite"]*$a["price"]*(1-$a["remise"]/100);        
        }
        $achat->montant = $montant;
        $achat->save();
        foreach ($produits as $a) {
            if($a['casio'] == "nouveau"){
                $achat->produits()->attach($a["id"],[
                    "quantite_achat_produit" => $a["quantite"],
                    "prix_achat" => $a["price"],
                    "remise" => $a["remise"],
                    "type" => "nouveau",
                    "quantite_restante" => -1
                ]);
                $produit = \App\Produit::find($a["id"]);
                $new = $a["quantite"]*$a["price"]*(1-$a["remise"]/100);
                $old = $produit->prix_achat * $produit->quantite;
                $newP = $a["quantite"]*$a["price"];
                $oldP = $produit->prix_achatP * $produit->quantite;
                $produit->quantite = $produit->quantite + $a["quantite"];
                $produit->prix_achat = ($new + $old)/$produit->quantite;
                $produit->prix_achatP = $oldP > 0 ? ($newP + $oldP)/$produit->quantite : $a["price"];
                $produit->save();
            }else{
                $achat->produits()->attach($a["id"],[
                    "quantite_achat_produit" => $a["quantite"],
                    "prix_achat" => $a["price"],
                    "remise" => $a["remise"],
                    "type" => "casio",
                    "quantite_restante" => $a["quantite"]
                ]);
                $produit = \App\Produit::find($a["id"]);
                $new = $a["quantite"]*$a["price"]*(1-$a["remise"]/100);
                $old = $produit->prix_achat_casio * $produit->quantite_casio;
                $produit->quantite_casio = $produit->quantite_casio + $a["quantite"];
                $produit->prix_achat_casio = ($new + $old)/$produit->quantite_casio;
                $produit->save();
            }
        }
        
        return $achat;
    }

    public function deleteAchat($id){
        $achat = \App\Achat::find($id);
        $achat_old = $this->getAchat($id);
        if($achat){
            foreach ($achat_old[0]["produits"] as $pro) {
                $produit = \App\Produit::find($pro["id"]);
                $prix = $pro['prix']*$pro['quantite']*(1-$pro['remise']/100);
                $prixP = $pro['prix']*$pro['quantite'];
                $old_prix = 0;
                $old_prixP = 0;
                $old_quantite = 0;
                if($pro['type'] == "nouveau"){
                    $old_quantite = $produit->quantite - $pro['quantite'];
                    if($old_quantite == 0){
                        //$produit->prix_vente = 0;
                    }else{
                        $old_prix = (($produit->prix_achat*$produit->quantite)-$prix)/$old_quantite; 
                        $old_prixP = (($produit->prix_achatP*$produit->quantite)-$prixP)/$old_quantite; 
                    }     
                    $produit->prix_achat = $old_prix; 
                    $produit->prix_achatP = $old_prixP;           
                    $produit->quantite = $old_quantite;
                }else{
                    $old_quantite = $produit->quantite_casio - $pro['quantite'];
                    if($old_quantite == 0){
                        //$produit->prix_vente_casio  = 0;
                    }else{
                        $old_prix = (($produit->prix_achat_casio*$produit->quantite_casio)-$prix)/$old_quantite; 
                    }  
                    $produit->prix_achat_casio = $old_prix;              
                    $produit->quantite_casio = $old_quantite;
                }
                $produit->save();
            }
            $achat->produits()->detach();
            if($achat->delete()){
                return response()->json(array(
                    'code'      =>  200,
                    'message'   =>  "L'achat a été supprimé"
                ), 200);  
                 
            }else
                return response()->json(array(
                    'code'      =>  404,
                    'message'   =>  'L achat n\'existe pas.'
                ), 404); 
            
        }
         return response()->json(array(
                    'code'      =>  404,
                    'message'   =>  'L achat n\'existe pas.'
                ), 404); 
    }

    public function getAchat($id){


        $rawAchats = \App\Achat::with([
            'agence',

            'produits' => function($query){
                $query -> select("nom","produits.id","max","produits.quantite");
        },
            'fournisseur' => function($q){
                $q -> select("id","name");
        },
            'user' => function($q){
                $q -> select("id","name");
        },        

        ])->where("id",$id)->get();

        $achats = [];

        foreach ($rawAchats as $achat) {
            $produits = [];
            foreach ($achat['produits'] as $produit) {
                $produits[] = [
                    "nom" => $produit['nom'],
                    "id" => $produit['id'],
                    "max" => $produit['max'],
                    "quantite" => $produit['pivot']['quantite_achat_produit'],
                    "quantite_produit" => $produit["quantite"],
                    "prix" => $produit['pivot']['prix_achat'],
                    "remise" => $produit['pivot']['remise'],
                    "type" => $produit['pivot']['type'],
                    "quantite_restante" => $produit['pivot']['quantite_restante']
                ];
            }
            $achats[] = [
                "id" => $achat["id"],
                "user_id" => $achat["user_id"],
                "montant" => $achat["montant"],
                "fournisseur" => $achat["fournisseur"]["name"],
                "fournisseur_id" =>$achat["fournisseur"]["id"],
                "user" => $achat["user"]["name"],
                "user_id" =>$achat["user"]["id"],
                "date" => $achat['created_at']->toDateString(),
                "agence" => $achat["agence"],
                "produits" => $produits
            ];
            
        }

        return $achats;
    }

    public function updateAchat(Request $request){
        $achat = \App\Achat::find($request->input('id'));
        $achat->fournisseur_id = $request->input('fournisseur_id');
        $montant = 0;
        $produits = $request->input('produits');
        $achat_old = $this->getAchat($request->input('id'));
        foreach ($produits as $a) {
            $new = $a["quantite"]*$a["price"]*(1-$a["remise"]/100);
            $montant += $new;         
            foreach($achat_old[0]["produits"] as $pro){
             $produit = \App\Produit::find($a["id"]);
             $old_quantite = 0;
             $old_prix = 0;
             $old_prixP = 0;
             $prix = $pro['prix']*$pro['quantite']*(1-$pro['remise']/100);
             $prixP = $pro['prix']*$pro['quantite'];
             if ($pro['id'] == $a['id']){
                if($pro['type'] == "nouveau"){
                    $old_quantite = $produit->quantite - $pro['quantite'];
                    if($old_quantite == 0){
                        //$produit->prix_vente = 0;
                    }else{
                        $old_prix = (($produit->prix_achat*$produit->quantite)-$prix)/$old_quantite; 
                        $old_prixP = (($produit->prix_achatP*$produit->quantite)-$prixP)/$old_quantite; 
                    }     
                    $produit->prix_achat = $old_prix; 
                    $produit->prix_achatP = $old_prixP;           
                    $produit->quantite = $old_quantite;
                }else{
                    $old_quantite = $produit->quantite_casio - $pro['quantite'];
                    if($old_quantite == 0){
                        //$produit->prix_vente_casio  = 0;
                    }else{
                        $old_prix = (($produit->prix_achat_casio*$produit->quantite_casio)-$prix)/$old_quantite; 
                    }  
                    $produit->prix_achat_casio = $old_prix;              
                    $produit->quantite_casio = $old_quantite;
                }
              }
              $produit->save(); 
            }
            $p = \App\Produit::find($a["id"]);  
            if($a['casio'] == "nouveau"){
                $old = $p->prix_achat * $p->quantite;
                $oldP = $p->prix_achatP * $p->quantite;
                $newP = $a["quantite"]*$a["price"];
                $p->quantite = $p->quantite + $a["quantite"];
                $p->prix_achat = ($new + $old)/$p->quantite;
                $p->prix_achatP = ($newP + $oldP)/$p->quantite;
                $achat->produits()->sync([
                    $a["id"]=>["quantite_achat_produit" => $a["quantite"],
                          "prix_achat" => $a["price"],
                          "remise" => $a["remise"],
                          "type" => $a["casio"],
                          "quantite_restante" => -1]
                        ], false); 
            }else{
                $old = $p->prix_achat_casio * $p->quantite_casio;
                $p->quantite_casio = $p->quantite_casio + $a["quantite"];
                $p->prix_achat_casio = ($new + $old)/$p->quantite_casio;
                $achat->produits()->sync([ 
                    $a["id"]=>["quantite_achat_produit" => $a["quantite"],
                          "prix_achat" => $a["price"],
                          "remise" => $a["remise"],
                          "type" => $a["casio"],
                          "quantite_restante" => $a['quantite']]
                        ], false); 
            }
            $p->save();
        }
        $achat->montant = $montant;
        $achat->save();
        return $achat;
    }

    public function getAchatsByIdUser($id){
        $list = [];
        $achats =  \App\Achat::where('user_id','=',$id)->get();
        foreach ($achats as $achat) {
            $l = [];
            $fournisseur = \App\Fournisseur::find($achat->fournisseur_id);
            $l['id'] = $achat->id;
            $l['fournisseur'] = $fournisseur->name;
            $l['montant'] = $achat->montant;
            $l['date'] = $achat->created_at;
            array_push($list, $l);
        }
        return $list;
    }
}
