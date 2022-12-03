<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
class CommissionController extends Controller
{
    public function getCommissionsByIdUser($id){
    	$list = [];
        $commissionsBuilder = \App\Commission::where('user_id','=',$id)
        ->whereMonth('created_at', '=', Carbon::now()->month)
        ->orderBy('created_at', 'desc');
        if(request()->has('date')){
            $commissionsBuilder = $commissionsBuilder
                ->whereDate('created_at',request()->get('date'));
        }
        
        $commissions = $commissionsBuilder->get();
    	foreach ($commissions as $commission) {
    		$l = [];
            $l['id'] = $commission->id;
    		$l['commission'] = $commission->commission;
            $l['quantite_vente_produit'] = $commission->quantite != null ? $commission->quantite : 0;
            $l['date'] = $commission->created_at->toDateString();
            if($commission->produit_id != null){
                $p = \App\Produit::find($commission->produit_id);
                $l['produit'] = $p->nom;
            }else{
                $l['produit'] = "service";
            }
            if($commission->service_id == null){          
    		if($commission->retour_id == null){              
                $ventes = \App\Vente::with([
                                'produits' => function($query){
                                 $query -> select("nom","produits.id");
                                 }
                                         ])->where('id',$commission->vente_id)->get();
                foreach ($ventes as $vente) {
                $client = \App\Client::find($vente['client_id']);               
                $l['client'] = $client['nom'];
                    foreach ($vente['produits'] as $produit){
                    if($produit['id'] == $commission->produit_id){
                        $l['remise'] = $produit['pivot']['remise'];
                        $l['prix_vente'] = $produit['pivot']['prix_vente'];                      
                    }
                    }
                }

    		}else{
                $retours = \App\Retour::with([
                                'produits' => function($query){
                                 $query -> select("nom","produits.id");
                                 }
                                         ])->where('id',$commission->retour_id)->get();
                foreach ($retours as $retour) {
                $client = \App\Client::find($retour['client_id']);               
                $l['client'] = $client['nom'];
                    foreach ($retour['produits'] as $produit){
                    if($produit['id'] == $commission->produit_id){
                        $l['remise'] = $produit['pivot']['remise'];
                        $l['prix_vente'] = $produit['pivot']['prix_vente'];
                    }
                    }
                }

    		}
            }else{
                $service = \App\Service::find($commission->service_id);
                $l['client'] = "service";
                $l['remise'] = 0;
                $l['prix_vente'] = $service->montant;
            }
    		array_push($list, $l);
    	}

    	return $list;
    }
}
