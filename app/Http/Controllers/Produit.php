<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Produit extends Controller
{
    public function getProduits(){
        $list = [];
        $gain = \App\Vente::where('agence_id', Auth::user()->agence_id)
                            ->whereYear('created_at',Carbon::now()->year)
                            ->sum('gain');
        $totalAchats = DB::table('produits')->select(DB::raw('sum(prix_achat * quantite + prix_achat_casio * quantite_casio) as totalAchats'))
                                    ->where('agence_id', Auth::user()->agence_id)
                                    ->first();
        $list['totalAchats'] = $totalAchats ? $totalAchats->totalAchats : 0;
        $list['gain'] = $gain; 
         
        if(request()->has('keyword')){
            $keyword = request()->get("keyword");

            if(Auth::user()->role == "user"){
                /*return \App\Produit::where("agence_id", Auth::user()->agence_id)
                    ->where('nom','like','%'.$keyword.'%')
                    ->orWhere('code','like','%'.$keyword.'%')
                    ->get(); */
                $list['produits'] = \App\Produit::where(function ($query) {
                                $query->where("agence_id", Auth::user()->agence_id);
                            })->where(function ($query) use($keyword) {
                                $query->where('nom','like','%'.$keyword.'%')
                                      ->orWhere('code','like','%'.$keyword.'%');
                    })->get();
                return $list;
            }

            $list['produits'] = DB::table('produits')
            ->join('agences', 'produits.agence_id', '=', 'agences.id')
            ->select('produits.*', 'agences.nom as agence_nom')
            ->where('produits.nom','like','%'.$keyword.'%')
            ->orWhere('produits.code','like','%'.$keyword.'%')
            ->get();
            return $list;
        }
        if(Auth::user()->role == "user"){
            $list['produits'] = \App\Produit::where("agence_id", Auth::user()->agence_id)
            ->get();
            return $list;
        }
        //return \App\Produit::all();
        $list['produits'] = DB::table('produits')
            ->join('agences', 'produits.agence_id', '=', 'agences.id')
            ->select('produits.*', 'agences.nom as agence_nom')
            ->get();
        return $list;
    }

    public function getProductsByUser(){
        if(request()->has('keyword')){
            $keyword = request()->get("keyword");
            return \App\Produit::where("agence_id", Auth::user()->agence_id)
                    ->where('nom','like','%'.$keyword.'%')
                    ->orWhere('code','like','%'.$keyword.'%')
                    ->get();

        }
        return \App\Produit::where("agence_id", Auth::user()->agence_id)
            ->get();
    }

    public function postProduit(Request $request){
        
        $p = \App\Produit::where('code', '=' ,$request->input('code'))->get();
        if(count($p) != 0){
           return response()->json(array('message' => "code à barre déja utilisé"), 401); 
        }
        if(Auth::user()->role == "user"){
            return response()->json(array('message' => "Action non autorisée"), 401); 
        }
    	$produit = \App\Produit::where('nom', '=' ,$request->input('name'))->first();
    	if ($produit == null)
    	{

            $agences = \App\Agence::all();
            foreach ($agences as $agence) {
                $produit = new  \App\Produit; 
                $produit->nom = $request->input('name');
                $produit->code = $request->input("code");
                $produit->prix_achat = 0;//$request->input('prixAchat');
                $produit->prix_achatP = 0;
                $produit->prix_vente = 0;//$request->input('prixVente');
                $produit->prix_vente_gros = 0;
                $produit->agence_id = $agence->id;
                $produit->quantite = 0;
                $produit->quantite_casio = 0;
                $produit->prix_achat_casio = 0;
                $produit->prix_vente_casio = 0;
                $produit->max = $request->input('max');
                $produit->min = $request->input('min');
                $produit->points_g = $request->input('pointsG');
                $produit->points_d = $request->input('pointsD');
                $produit->pourcentage_g = $request->input('pourcentageG');
                $produit->pourcentage_d = $request->input('pourcentageD');
                $produit->remise = $request->input('remise');
                $produit->save();
            }
	        
    	}
        else 
        {
            $a = $request->input('prixAchat')*$request->input('quantite');
            $b = $produit->prix_achat * $produit->quantite;
            $produit->quantite = $produit->quantite + $request->input('quantite');
            $produit->prix_achat = ($a + $b)/$produit->quantite;
            $produit->prix_achatP = $produit->prix_achat;
            $produit->prix_vente = 0;
            $produit->save();
        }
        
	    
        return $produit;
    }

    public function deleteProduit($id)
    { 
    $produit = \App\Produit::find($id); 
    if($produit->delete())
        { 
            return response()->json(array( 'code' => 200, 'message' => "Le produit a été supprimé", 'status' => true, ), 200); 
        }
    else
        { 
            return response()->json(array( 'code' => 300, 'message' => "Une erreur est survenue", 'status' => false, ), 300); 
        } 
    }

    public function getProduit($id){
        return \App\Produit::find($id);
    }

     public function updateProduit(Request $request){
        $produit = \App\Produit::find($request->input('id'));
        $produits = \App\Produit::where('code',$produit->code)->get();
        foreach ($produits as $p) 
        {
            $p->nom = $request->input('name');
            //$p->prix_achat = $request->input('prixAchat');
            //$p->prix_vente = $request->input('prixVente');
            //$p->quantite = $request->input('quantite');            
            $p->max = $request->input('max');
            $p->min = $request->input('min');
            $p->points_g = $request->input('pointsG');
            $p->points_d = $request->input('pointsD');
            $p->pourcentage_g = $request->input('pourcentageG');
            $p->pourcentage_d = $request->input('pourcentageD');
            //$p->quantite_casio = $request->input('quantiteCasio');
            //$p->prix_achat_casio = $request->input('prixAchatCasio');
            //$p->prix_vente_casio = $request->input('prixVenteCasio');
            if($produit->agence_id != 5 && $p->agence_id != 5){
                $p->remise = $request->input('remise');
                $p->prix_vente_gros = $request->input('prixVenteGros');
                $p->prix_achat = $request->input('prixAchat');
                $p->prix_vente = $request->input('prixVente');
            }
            $p->save();        
        }
        $produit->remise = $request->input('remise');
        $produit->prix_vente_gros = $request->input('prixVenteGros');
        $produit->prix_achat = $request->input('prixAchat');
        $produit->prix_achatP = $request->input('prixAchat');
        $produit->prix_vente = $request->input('prixVente');
        $produit->prix_achat_casio = $request->input('prixAchatCasio');
        $produit->prix_vente_casio = $request->input('prixVenteCasio');

        $produit->save(); 
        return $produit; 
    }

    public function productHistory($id){
        $achats = DB::table('achat_produit')
            ->join('achats', 'achat_produit.achat_id', '=', 'achats.id')
            ->join('users', 'achats.user_id', '=', 'users.id')
            ->join('fournisseurs', 'achats.fournisseur_id', '=', 'fournisseurs.id')
            ->select('achat_produit.quantite_achat_produit', 'achat_produit.prix_achat',
             'achat_produit.remise', 'achat_produit.type', 'achats.created_at as date', 'users.name as user',
             'fournisseurs.name as fournisseur')
            ->where('achat_produit.produit_id','=',$id)
            ->get();

        $ventes = DB::table('vente_produit')
            ->join('ventes', 'vente_produit.vente_id', '=', 'ventes.id')
            ->join('users', 'ventes.user_id', '=', 'users.id')
            ->join('clients', 'ventes.client_id', '=', 'clients.id')
            ->select('vente_produit.quantite_vente_produit', 'vente_produit.prix_vente',
             'vente_produit.remise', 'vente_produit.type', 'ventes.created_at as date', 'users.name as user',
             'clients.nom as client')
            ->where('vente_produit.produit_id','=',$id)
            ->get();
        $history = [];
        $history['achats'] = $achats; $history['ventes'] = $ventes;
        return $history;
    }
}
