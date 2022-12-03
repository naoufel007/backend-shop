<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use PDF;

class VenteController extends Controller
{
    const itemsPerPage = 20;

    public function ventePDF($id){
        $vente = $this->getVente($id)[0];
        $pdf = PDF::loadView("vente-invoice", [
            "vente" => $vente
        ]);
        return $pdf->stream("vente-$id.pdf");
    }

    public function getVentes()
    {

        
        $queryBuilder = \App\Vente::with([
            'agence' => function($query){
                $query -> select("id","nom");
            },
            'produits' => function($query){
                $query -> select("nom");
            },
            'client' => function($q){
                $q -> select("id","nom");
            },
            'user' => function($q){
                $q -> select("id","name");
            },
        ])->orderBy('id', 'desc');

        if(auth()->user()->role == "user"){
            $queryBuilder->where("agence_id",auth()->user()->agence_id);
        }         
        if(request()->has('date')){
            $date = Carbon::parse(request()->get("date"));
            $queryBuilder->whereDate('created_at', '=', $date->toDateString());
        }

        $paginator = $queryBuilder->paginate(VenteController::itemsPerPage);
        $rawVentes = $paginator->items();

        $ventes = [];
    	foreach ($rawVentes as $vente) {
    		$produits = [];
    		foreach ($vente['produits'] as $produit) {
    			$produits[] = [
    				"nom" => $produit['nom'],
    				"quantite" => $produit['pivot']['quantite_vente_produit'],
    				"prix" => $produit['pivot']['prix_vente'],
                    "remise" => $produit['pivot']['remise'],
                    "type" => $produit['pivot']['type'],
    			];
    		}
    		$ventes[] = [
    			"id" => $vente["id"],
    			"montant" => $vente["montant"],
                "client" => $vente["client"],
                "user" => $vente["user"],
    			"date" => $vente['created_at']->toDateString(),
                "gain" => $vente['gain'],
    			"agence" => $vente["agence"],
                "type_vente" => $vente["type_vente"],
    			"produits" => $produits
    		];
    		
    	}

        return [
            "ventes" => $ventes,
            "total" => $paginator->total(),
            "currentPage" => $paginator->currentPage(),
            "itemsPerPage" => VenteController::itemsPerPage,
            "displayPagination" => $paginator->total() > VenteController::itemsPerPage
        ];

    }

    public function deleteVente($id){
        $vente = \App\Vente::find($id);
        $old_vente = $this->getVente($id);
        $old_client = \App\Client::find($vente->client_id); 
        DB::table('commissions')->where('vente_id','=',$vente->id)
                                ->delete();
        foreach ($old_vente[0]["paiments"] as $pai) {
                if($pai["type"] == 'C' ){
                    $credit = \App\Credit::where('client_id',$old_client->id)
                                          ->where('montant',$pai["montant"])
                                          ->where('user_id',$vente->user_id)->get();
                    //var_dump($credit[0]);
                    if($credit[0]){
                    DB::table('paiment_credits')->where('credit_id',$credit[0]->id)
                                                ->delete(); 
                    DB::table('credits')->where('id',$credit[0]->id)
                                       ->delete();                                            
                    }                     
                                    
                }
                if($pai["type"] == 'P'){
                    $old_client->points += $pai["montant"]*100;
                }
        }
        
        foreach ($old_vente[0]["produits"] as $pro) {
            $p = \App\Produit::find($pro["id"]); 
            //quantité
            if($pro['type'] == 'nouveau'){
                $p->quantite += $pro['quantite'];
            }else{
                $p->quantite_casio += $pro['quantite'];
                $q = $pro['quantite'];
                $achats_produits = DB::table('achat_produit')->where('produit_id','=',$pro["id"])
                                                             ->where('type','=','casio')
                                                             ->where('quantite_restante','>',-1)
                                                             ->orderBy('created_at', 'asc')->get();
                foreach ($achats_produits as $pa) {
                    if($pa->quantite_restante != $pa->quantite_achat_produit && $q != 0){
                        $diff = $pa->quantite_achat_produit - $pa->quantite_restante;
                            if($q > $diff){
                                DB::table('achat_produit')->where('id','=',$pa->id)
                                                          ->update(['quantite_restante'=>$pa->quantite_achat_produit]);
                                $q = $q - $diff;  
                            }else{
                                DB::table('achat_produit')->where('id','=',$pa->id)
                                                          ->update(['quantite_restante'=>$pa->quantite_restante + $q]);
                                $q = 0; 
                            }
                    }
                }

            }        
                        
            //points old_client                        
            if($vente->type_vente == 0){
                $old_client->points -= $p->points_d*$pro['quantite'];
            }else{
                $old_client->points -= $p->points_g*$pro['quantite'];
            }
            $old_client->save();
                    
            $p->save();
        }                  
                
        $old_client->save();
        DB::table('paiment_ventes')->where('vente_id','=',$vente->id)
                                   ->delete();
        if($vente){         
            if($vente->delete()){               
                return response()->json(array(
                    'code'      =>  200,
                    'message'   =>  "La vente a été supprimé"
                ), 200);  
                 
            }else
                return response()->json(array(
                    'code'      =>  404,
                    'message'   =>  'La vente n\'existe pas.'
                ), 404); 
            
        }
         return response()->json(array(
                    'code'      =>  404,
                    'message'   =>  'La vente n\'existe pas.'
                ), 404); 
    }


    public function postVente(Request $request)
    {
    	try{
            $gain = 0;
            $vente = new  \App\Vente;
            $vente->client_id = $request->input('client_id');
            $vente->user_id =  Auth::user()->id;
            $vente->agence_id = auth()->user()->agence_id;
            $produits = $request->input('produits');
            $client = \App\Client::find($request->input('client_id'));
            $paiments = $request->input('paiments');
            if($request->input('typeVente') == "D"){
                $vente->type_vente = 0; //detail
            }else{
                $vente->type_vente = 1; //gros
            }
            $vente_montant = 0;
            foreach ($produits as $a) {
                $produit = \App\Produit::find($a["id"]);
                // verification du quantité et du prix de vente 
                if($a['casio'] == "nouveau"){
                    if($produit->quantite < $a["quantite"]){
                    return response()->json(array(
                    'code'      =>  503,
                    'message'   =>  "il existe que : ".$produit->quantite." dans le stock pour le produit :".$produit->nom
                    ), 503); 
                    }
                    if($produit->prix_vente == 0){
                    return response()->json(array(
                    'code'      =>  503,
                    'message'   =>  "Le prix de vente nouveau du produit :".$produit->nom ."est 0, il faut saisir un prix"
                    ), 503); 
                    } 
                    $gain += $a["quantite"] * ($a["price"]*(1-$a["remise"]/100) - $produit->prix_achat);             
                }else{
                    if($produit->quantite_casio < $a["quantite"]){
                    return response()->json(array(
                    'code'      =>  503,
                    'message'   =>  "il existe que : ".$produit->quantite_casio." dans le stock pour le produit :".$produit->nom
                    ), 503); 
                    }
                    if($produit->prix_vente_casio == 0){
                    return response()->json(array(
                    'code'      =>  503,
                    'message'   =>  "Le prix de vente casio du produit :".$produit->nom ."est 0, il faut saisir un prix"
                    ), 503); 
                    }
                    $gain += $a["quantite"] * ($a["price"]*(1-$a["remise"]/100) - $produit->prix_achat_casio); 
                }                                   
                $vente_montant += $a["quantite"]*$a["price"]*(1-$a["remise"]/100);                 
            }
            $sum = 0;
            if($paiments){               
                foreach ($paiments as $paiment) {                   
                    if($paiment["type"] == 'C'){
                        // if he has a credit in an other agency
                        $credits = \App\Credit::where('client_id','=',$client->id)
                                              ->where('agence_id','<>',Auth::user()->agence_id)->first();
                        if($credits != null){
                            $agence_nom = \App\Agence::find($credits->agence_id)->nom;
                            return response()->json(array(
                                'code'      =>  501,
                                'message'   =>  "Vous avez un autre credit dans l'agence : ". $agence_nom . " avec le montant : ". $credits->montant
                            ), 400); 
                        }
                        // if he still can take a credit
                        $credits_sum = \App\Credit::where('client_id','=',$client->id)
                                                   ->where('statut','=','N')->sum('reste');
                        if($credits_sum + $paiment["montant"] > $client->credit){
                            return response()->json(array(
                                'code'      =>  501,
                                'message'   =>  "Votre credit est de : ". $credits_sum . "avec ce nouveau credit de : ".$paiment["montant"] . ", vous depasserez votre seuil de :". $client->credit
                            ), 400); 
                        }
                    }
                    if($paiment["type"] == 'H' && !array_key_exists("checkNumber",$paiment)){                          
                        return response()->json(array(
                             'code'      =>  501,
                             'message'   =>  "Il faut fournir un numero de chèque pour les paiements de ce type"
                        ), 400);                        
                    }
                    if($paiment["type"] == 'P' && $client->points < $paiment["montant"]*100){
                        return response()->json(array(
                             'code'      =>  501,
                             'message'   =>  "le client n'a que ".$client->points ." de points."
                        ), 400);                              
                    }
                    $sum += $paiment["montant"];
                }
                if((string)$sum != (string)$vente_montant){
                    return response()->json(array(
                        'code'      =>  501,
                        'message'   =>  "la somme des paiements est ".$sum." different de celui du mantant de la vente"
                    ), 400); 
                }
            }else{
                return response()->json(array(
                    'code'      =>  501,
                    'message'   =>  "Il faut saisir des paimements."
                ), 400); 
                    
            }
            $vente->montant = $vente_montant;
            $vente->gain = $gain;
            $vente->save();
            foreach ($produits as $a)
            {
                $produit = \App\Produit::find($a["id"]); 
                if($a['casio'] == "nouveau"){
                    if($produit->prix_achat < $a["price"]*(1-$a["remise"]/100)){   
                        $commission = new \App\Commission;
                        $commission->vente_id = $vente->id;
                        $commission->user_id = auth()->user()->id;
                        $commission->produit_id = $a["id"];
                        $commission->quantite = $a["quantite"];                 
                        if($request->input('typeVente') == "D"){
                            $commission->commission = $a["quantite"]*($a["price"]*(1-$a["remise"]/100)
                                                  - $produit->prix_achat)*$produit->pourcentage_d/100;
                        }else{
                            $commission->commission = $a["quantite"]*($a["price"]*(1-$a["remise"]/100)
                                                  - $produit->prix_achat)*$produit->pourcentage_g/100;
                        }
                        $commission->save();
                    }                        
                    $produit->quantite = $produit->quantite - $a["quantite"];     
                    $produit->save();
                    $vente->produits()->attach($a["id"],[
                    "quantite_vente_produit" => $a["quantite"],
                    "prix_vente_recommande" => $a["price_recommande"],
                    "prix_vente" => $a["price"],
                    "remise" => $a["remise"],
                    "type" => "nouveau",
                    ]);
                }else{
                    if($produit->prix_achat_casio < $a["price"]*(1-$a["remise"]/100))
                    {
                        $user = \App\User::find(auth()->user()->id);    
                        $commission = new \App\Commission;
                        $commission->vente_id = $vente->id;
                        $commission->user_id = auth()->user()->id;
                        $commission->produit_id = $a["id"];
                        $commission->quantite = $a["quantite"];                  
                        $commission->commission = $a["quantite"]*($a["price"]*(1-$a["remise"]/100)
                                                  - $produit->prix_achat_casio)*$user->p_casio_vente/100;
                        $commission->save();
                        $q = $a["quantite"];
                        $achats_produits = DB::table('achat_produit')->where('produit_id','=',$a["id"])
                                                                     ->where('type','=','casio')
                                                                     ->where('quantite_restante','>',0)
                                                                     ->orderBy('created_at', 'asc')->get();
                            foreach ($achats_produits as $pa)
                            {
                                if($q != 0){
                                    if($q <= $pa->quantite_restante)
                                    {
                                    $achat = \App\Achat::find($pa->achat_id);
                                    $u =  \App\User::find($achat->user_id);
                                    $commission = new \App\Commission;
                                    $commission->vente_id = $vente->id;
                                    $commission->user_id = $achat->user_id;
                                    $commission->produit_id = $a["id"]; 
                                    $commission->quantite = $q;                 
                                    $commission->commission = $q*($a["price"]*(1-$a["remise"]/100)
                                                                - $produit->prix_achat_casio)*$u->p_casio_achat/100;
                                    $commission->save(); 
                                    DB::table('achat_produit')->where('id','=',$pa->id)
                                                              ->update(['quantite_restante'=>$pa->quantite_restante-$q]);
                                    $q =0;
                                    }
                                    else
                                    {
                                    $achat = \App\Achat::find($pa->achat_id);
                                    $u =  \App\User::find($achat->user_id);
                                    $commission = new \App\Commission;
                                    $commission->vente_id = $vente->id;
                                    $commission->user_id = $achat->user_id;
                                    $commission->produit_id = $a["id"];
                                    $commission->quantite = $pa->quantite_restante;                  
                                    $commission->commission = $pa->quantite_restante*($a["price"]*(1-$a["remise"]/100)
                                                                - $produit->prix_achat_casio)*$u->p_casio_achat/100;
                                    $commission->save(); 
                                    DB::table('achat_produit')->where('id','=',$pa->id)
                                                              ->update(['quantite_restante'=>0]);      
                                    $q = $q - $pa->quantite_restante;                             
                                    } 
                                }
                            }
                    }                        
                    $produit->quantite_casio = $produit->quantite_casio - $a["quantite"];     
                    $produit->save();
                    $vente->produits()->attach($a["id"],[
                    "quantite_vente_produit" => $a["quantite"],
                    "prix_vente_recommande" => $a["price_recommande"],
                    "prix_vente" => $a["price"],
                    "remise" => $a["remise"],
                    "type" => "casio",
                    ]);
                }  
            }

            if($request->input('paiments')){               
                foreach ($paiments as $paiment){
                    $newP = new \App\PaimentVente;
                    $newP->vente_id = $vente->id;
                    $newP->user_id = Auth::user()->id;                    
                    $newP->type = $paiment["type"];
                    $newP->montant = $paiment["montant"];
                    if($paiment["type"] == 'C'){                      
                        $credit = new \App\Credit;
                        $credit->user_id = Auth::user()->id;
                        $credit->montant = $paiment["montant"];
                        $credit->reste = $paiment["montant"];
                        $credit->agence_id = auth()->user()->agence_id;
                        $credit->client_id = $client->id;
                        $credit->save();
                    }
                    if($paiment["type"] == 'H'){
                        $newP->numero_cheque = $paiment["checkNumber"];
                    }
                    if($paiment["type"] == 'P'){
                        $client->points -= $paiment["montant"]*100;
                    }
                    $newP->save();
                }
            }
            foreach ($produits as $a)
            {
                $produit = \App\Produit::find($a["id"]);
                if($request->input('typeVente') == "D")
                {
                 $client->points += $produit->points_d*$a["quantite"];
                }else{
                $client->points += $produit->points_g*$a["quantite"];
                }

                if(Auth::user()->role == "admin"){
                    $agences = \App\Agence::all();
                    $identifier = 0;
                    foreach ($agences as $agence) {
                        if($agence->nom == $client->nom){
                            $identifier = $agence->id;
                        }
                    }
                    if($identifier > 0 && $a["prixVente"] > 0){
                        if($request->input('typeVente') == "D"){
                            DB::table('produits')->where('nom','=',$produit->nom)
                                                 ->where('agence_id','=',$identifier)
                                                 ->update(['prix_vente'=>$a["prixVente"]]);                                   
                        }else{
                            DB::table('produits')->where('nom','=',$produit->nom)
                                                 ->where('agence_id','=',$identifier)
                                                 ->update(['prix_vente_gros'=>$a["prixVente"]]);   
                        }
                    }
                }
            }
            
            $client->save();
            return $vente;
        }
        catch(Exception $e)
        {
            return response()->json(array(
                    'code'      =>  500,
                    'message'   =>  'Une erreur est survenue'
                ), 500); 
        }
    }

    public function getVente($id){


        $rawVentes = \App\Vente::with([
            'agence',
            'paiments',
            'paiments.user:id,name',
            'user'=> function($q){
                $q -> select("id","name");
            },
            'produits' => function($query){
                $query -> select("nom","produits.id","min","produits.quantite","prix_vente_casio");
            },
            'client' => function($q){
                $q -> select("id","nom");
            },

        ])->where("id",$id)->get();

        $ventes = [];

        foreach ($rawVentes as $vente) {
            $produits = [];
            foreach ($vente['produits'] as $produit) {
                $produits[] = [
                    "nom" => $produit['nom'],
                    "id" => $produit['id'],
                    "min" => $produit['min'],
                    "quantite" => $produit['pivot']['quantite_vente_produit'],
                    "quantite_produit" => $produit["quantite"],
                    "prix" => $produit['pivot']['prix_vente'],
                    "prixCasio" => $produit['prix_vente_casio'],
                    "remise" => $produit['pivot']['remise'],
                    "type" => $produit['pivot']['type'],
                ];
            }
            $ventes[] = [
                "id" => $vente["id"],
                "montant" => $vente["montant"],
                "client" => $vente["client"],
                "client_id" =>$vente["client"]["id"],
                "user" => $vente["user"],
                "date" => $vente['created_at']->toDateString(),
                "agence" => $vente["agence"],
                "type_vente" => $vente["type_vente"],
                "produits" => $produits,
                "paiments" => $vente["paiments"],
                "gain" => $vente["gain"]
            ];
            
        }

        return $ventes;
    }

    public function updateVente(Request $request){
        try{
            $gain = 0;
            $vente = \App\Vente::find($request->input('id'));
            $produits = $request->input('produits');
            $vente_montant = 0;
            $old_vente =  $this->getVente($request->input('id'));
            $old_client = \App\Client::find($vente->client_id);
            $client = \App\Client::find($request->input('client_id'));
            $vente->client_id = $request->input('client_id');
            $paiments = $request->input('paiments');
            foreach ($produits as $a) {
                $vente_montant += $a["quantite"]*$a["price"]*(1-$a["remise"]/100);
                $produit = \App\Produit::find($a["id"]);
                if($a['casio'] == "nouveau"){
                    $gain += $a["quantite"] * ($a["price"]*(1-$a["remise"]/100) - $produit->prix_achat); 
                }else{
                    $gain += $a["quantite"] * ($a["price"]*(1-$a["remise"]/100) - $produit->prix_achat_casio); 
                }
            }
            $sum = 0;
            if($paiments){               
                foreach ($paiments as $paiment) {                   
                    if($paiment["type"] == 'C'){
                        // if he has a credit in an other agency
                        $credits = \App\Credit::where('client_id','=',$client->id)
                                              ->where('agence_id','<>',Auth::user()->agence_id)->first();
                        if($credits != null){
                            $agence_nom = \App\Agence::find($credits->agence_id)->nom;
                            return response()->json(array(
                                'code'      =>  501,
                                'message'   =>  "Vous avez un autre credit dans l'agence : ". $agence_nom . " avec le montant : ". $credits->montant
                            ), 400); 
                        }
                        // if he still can take a credit
                        $credits_sum = \App\Credit::where('client_id','=',$client->id)
                                                   ->where('statut','=','N')->sum('reste');
                        if($credits_sum + $paiment["montant"] > $client->credit){
                            return response()->json(array(
                                'code'      =>  501,
                                'message'   =>  "Votre credit est de : ". $credits_sum . "avec ce nouveau credit de : ".$paiment["montant"] . ", vous depasserez votre seuil de :". $client->credit
                            ), 400); 
                        }
                    }
                    if($paiment["type"] == 'H' && !array_key_exists("checkNumber",$paiment)){                          
                        return response()->json(array(
                             'code'      =>  501,
                             'message'   =>  "Il faut fournir un numero de chèque pour les paiements de ce type"
                        ), 400);                        
                    }
                    if($paiment["type"] == 'P' && $client->points < $paiment["montant"]*100){
                        return response()->json(array(
                             'code'      =>  501,
                             'message'   =>  "le client n'a que ".$client->points ." de points."
                        ), 400);                              
                    }
                    $sum += $paiment["montant"];
                }
                
                if($sum != $vente_montant){
                    return response()->json(array(
                        'code'      =>  501,
                        'message'   =>  "la somme des paiements est different de celui du mantant de la vente"
                    ), 400); 
                }
            }else{
                return response()->json(array(
                    'code'      =>  501,
                    'message'   =>  "Il faut saisir des paimements."
                ), 400); 
                    
            }
            foreach($produits as $a){
                $p = \App\Produit::find($a["id"]);
                foreach($old_vente[0]["produits"] as $pro){
                    if($pro['id'] == $a["id"]){
                        $myQ = 0;
                        if($pro['type'] == 'nouveau'){
                            if($a['casio'] == "nouveau"){
                                if($p->quantite + $pro['quantite'] < $a["quantite"]){
                                    $myQ = $p->quantite + $pro['quantite'];
                                    return response()->json(array(
                                    'code'      =>  503,
                                    'message'   =>  "il existe que : ".$myQ." dans le stock pour le produit :".$p->nom
                                    ), 503);
                                }
                            }else{
                                if($p->quantite_casio < $a["quantite"]){
                                    return response()->json(array(
                                    'code'      =>  503,
                                    'message'   =>  "il existe que : ".$p->quantite_casio." dans le stock pour le produit :".$p->nom
                                    ), 503);
                                }
                            }
                        }else{
                            if($a['casio'] == "nouveau"){
                                if($p->quantite < $a["quantite"]){
                                    return response()->json(array(
                                    'code'      =>  503,
                                    'message'   =>  "il existe que : ".$p->quantite." dans le stock pour le produit :".$p->nom
                                    ), 503);
                                }
                            }else{
                                if($p->quantite_casio + $pro['quantite'] < $a["quantite"]){
                                    $myQ = $p->quantite_casio + $pro['quantite'];
                                    return response()->json(array(
                                    'code'      =>  503,
                                    'message'   =>  "il existe que : ".$myQ." dans le stock pour le produit :".$p->nom
                                    ), 503);
                                }
                            }
                        }
                    }
                } 
            }

            DB::table('commissions')->where('vente_id','=',$vente->id)
                                    ->delete();
            foreach ($old_vente[0]["paiments"] as $pai) {
                if($pai["type"] == 'C' ){
                    $credit = \App\Credit::where('client_id',$old_client->id)
                                          ->where('montant',$pai["montant"])
                                          ->where('user_id',$vente->user_id)->get();
                    //var_dump($credit[0]);
                    if($credit[0]){
                    DB::table('credits')->where('id',$credit[0]->id)
                                       ->delete(); 
                    DB::table('paiment_credits')->where('credit_id',$credit[0]->id)
                                                ->delete();                         
                    }                     
                                    
                }
                if($pai["type"] == 'P'){
                    $old_client->points += $pai["montant"]*100;
                }
            }
            DB::table('paiment_ventes')->where('vente_id','=',$vente->id)
                                    ->delete();
            foreach ($produits as $a) 
            {
                $p = \App\Produit::find($a["id"]);                   
                foreach ($old_vente[0]["produits"] as $pro) {
                    if($pro['id'] == $a["id"]){
                        //quantité
                        if($pro['type'] == 'nouveau'){
                            $p->quantite += $pro['quantite'];
                        }else{
                            $p->quantite_casio += $pro['quantite'];
                            $q = $pro['quantite'];
                            $achats_produits = DB::table('achat_produit')->where('produit_id','=',$a["id"])
                                                                         ->where('type','=','casio')
                                                                         ->where('quantite_restante','>',-1)
                                                                         ->orderBy('created_at', 'asc')->get();
                            foreach ($achats_produits as $pa) {
                                if($pa->quantite_restante != $pa->quantite_achat_produit && $q != 0){
                                    $diff = $pa->quantite_achat_produit - $pa->quantite_restante;
                                    if($q > $diff){
                                    DB::table('achat_produit')->where('id','=',$pa->id)
                                                              ->update(['quantite_restante'=>$pa->quantite_achat_produit]);
                                    $q = $q - $diff;  
                                    }else{
                                    DB::table('achat_produit')->where('id','=',$pa->id)
                                                              ->update(['quantite_restante'=>$pa->quantite_restante + $q]);
                                    $q = 0; 
                                    }
                                }
                            }

                        }
                        //points old_client                        
                        if($vente->type_vente == 0){
                            $old_client->points -= $p->points_d*$pro['quantite'];
                        }else{
                            $old_client->points -= $p->points_g*$pro['quantite'];
                        }
                        $old_client->save();
                    }
                }
                if($a['casio'] == "nouveau"){
                    if($p->quantite < $a["quantite"]){
                    return response()->json(array(
                    'code'      =>  503,
                    'message'   =>  "il existe que : ".$p->quantite." dans le stock pour le produit :".$p->nom
                    ), 503); 
                    }
                }
                else
                {
                    if($p->quantite_casio < $a["quantite"])
                    {
                    return response()->json(array(
                    'code'      =>  503,
                    'message'   =>  "il existe que : ".$p->quantite_casio." dans le stock pour le produit :".$p->nom
                    ), 503); 
                    }
                }
                                                           
                $vente->produits()->sync([
                    $a["id"]=>[
                    "quantite_vente_produit" => $a["quantite"],
                    "prix_vente_recommande" => $a["price_recommande"],
                    "prix_vente" => $a["price"],
                    "remise" => $a["remise"],
                    "type" => $a["casio"]]
                ], false);
                if($a['casio'] == "nouveau"){
                    if($p->prix_achat < $a["price"]*(1-$a["remise"]/100)){   
                        $commission = new \App\Commission;
                        $commission->vente_id = $vente->id;
                        $commission->user_id = $vente->user_id;
                        $commission->produit_id = $a["id"];
                        $commission->quantite = $a["quantite"];                 
                        if($request->input('typeVente') == "D"){
                            $commission->commission = $a["quantite"]*($a["price"]*(1-$a["remise"]/100)
                                                      - $p->prix_achat)*$p->pourcentage_d/100;                           
                        }else{
                            $commission->commission = $a["quantite"]*($a["price"]*(1-$a["remise"]/100)
                                                      - $p->prix_achat)*$p->pourcentage_g/100;                         
                        }
                        $commission->save();
                    }                        
                    $p->quantite = $p->quantite - $a["quantite"];    
                }else
                {
                    if($p->prix_achat_casio < $a["price"]*(1-$a["remise"]/100))
                    {
                        $user = \App\User::find($vente->user_id);    
                        $commission = new \App\Commission;
                        $commission->vente_id = $vente->id;
                        $commission->user_id = $user_id;
                        $commission->produit_id = $a["id"];
                        $commission->quantite = $a["quantite"];                  
                        $commission->commission = $a["quantite"]*($a["price"]*(1-$a["remise"]/100)
                                                  - $p->prix_achat_casio)*$user->p_casio_vente/100;
                        $commission->save();
                        $q = $a["quantite"];
                        $achats_produits = DB::table('achat_produit')->where('produit_id','=',$a["id"])
                                                                     ->where('quantite_restante','>',0)
                                                                     ->orderBy('created_at', 'asc')->get();
                            foreach ($achats_produits as $pa)
                            {
                                if($q != 0){
                                    if($q <= $pa->quantite_restante)
                                    {
                                    $achat = \App\Achat::find($pa->achat_id);
                                    $u =  \App\User::find($achat->user_id);
                                    $commission = new \App\Commission;
                                    $commission->vente_id = $vente->id;
                                    $commission->user_id = $achat->user_id;
                                    $commission->produit_id = $a["id"]; 
                                    $commission->quantite = $q;                 
                                    $commission->commission = $q*($a["price"]*(1-$a["remise"]/100)
                                                                - $p->prix_achat_casio)*$u->p_casio_achat/100;
                                    $commission->save(); 
                                    DB::table('achat_produit')->where('id','=',$pa->id)
                                                              ->update(['quantite_restante'=>$pa->quantite_restante-$q]);
                                    $q =0;
                                    }
                                    else
                                    {
                                    $achat = \App\Achat::find($pa->achat_id);
                                    $u =  \App\User::find($achat->user_id);
                                    $commission = new \App\Commission;
                                    $commission->vente_id = $vente->id;
                                    $commission->user_id = $achat->user_id;
                                    $commission->produit_id = $a["id"];
                                    $commission->quantite = $pa->quantite_restante;                  
                                    $commission->commission = $pa->quantite_restante*($a["price"]*(1-$a["remise"]/100)
                                                                - $p->prix_achat_casio)*$u->p_casio_achat/100;
                                    $commission->save(); 
                                    DB::table('achat_produit')->where('id','=',$pa->id)
                                                              ->update(['quantite_restante'=>0]);      
                                    $q = $q - $pa->quantite_restante;                             
                                    }

                                }

                            }
                    }                        
                    $p->quantite_casio = $p->quantite_casio - $a["quantite"];     
                }
                $p->save();   
            }
            if($request->input('typeVente') == "D"){
                $vente->type_vente = 0;               
            }else{
                $vente->type_vente = 1;
            }

            DB::table('commissions')->where('vente_id','=',$vente->id)
                                    ->update(['created_at'=>$vente->created_at]);
            $vente->montant = $vente_montant;
            $vente->gain = $gain;
            $vente->save();
            // a discuter 
            if($paiments){               
                foreach ($paiments as $paiment){
                    $newP = new \App\PaimentVente;
                    $newP->vente_id = $vente->id;
                    $newP->user_id = $vente->user_id;                    
                    $newP->type = $paiment["type"];
                    $newP->montant = $paiment["montant"];
                    if($paiment["type"] == 'C'){                      
                        $credit = new \App\Credit;
                        $credit->user_id = $vente->user_id;
                        $credit->montant = $paiment["montant"];
                        $credit->reste = $paiment["montant"];
                        $credit->agence_id = auth()->user()->agence_id;
                        $credit->client_id = $client->id;
                        $credit->created_at = $vente->created_at;
                        $credit->save();
                    }
                    if($paiment["type"] == 'H'){
                        $newP->numero_cheque = $paiment["checkNumber"];
                    }
                    if($paiment["type"] == 'P'){
                        $client->points -= $paiment["montant"]*100;
                    }
                    $newP->save();
                }
            }
            DB::table('paiment_ventes')->where('vente_id','=',$vente->id)
                                       ->update(['created_at'=>$vente->created_at]);
            foreach ($produits as $a)
            {
                $produit = \App\Produit::find($a["id"]);
                if($request->input('typeVente') == "D")
                {
                 $client->points += $produit->points_d*$a["quantite"];
                }else{
                $client->points += $produit->points_g*$a["quantite"];
                }
            }
            $client->save();
            return $vente;
        }catch(Exception $e){
            return response()->json(array(
                    'code'      =>  500,
                    'message'   =>  'Une erreur est survenue'
                ), 500); 
        }
    }

    public function getVentesByIdUser($id){
        $list = [];

        $queryBuilder = \App\Vente::where('user_id','=',$id);
        if(request()->has('date')){
            $date = Carbon::parse(request()->get("date"));
            $queryBuilder->whereDate('created_at', '=', $date->toDateString());
        }
        $paginator = $queryBuilder->paginate(VenteController::itemsPerPage);
        $ventes =  $paginator->items();
        foreach ($ventes as $vente) {
            $l = [];
            $client = \App\Client::find($vente->client_id);
            $l['id'] = $vente->id;
            $l['client'] = $client->nom;
            $l['montant'] = $vente->montant;
            $l['date'] = $vente->created_at->toDateString();
            array_push($list, $l);
        }
        return [
            "ventes" => $list,
            "total" => $paginator->total(),
            "currentPage" => $paginator->currentPage(),
            "itemsPerPage" => VenteController::itemsPerPage,
            "displayPagination" => $paginator->total() > VenteController::itemsPerPage

        ];
    }

    public function deletePaiment($vid,$pid)
    {
        $paiment = \App\PaimentVente::find($pid);
        $client_id = \App\Vente::find($vid)->client_id;
        $client = \App\Client::find($client_id);
        if($paiment){
            if($paiment->vente_id == $vid){
                $type_payment = $paiment->type;
                $montant = $paiment->montant;
                if($paiment->delete()){
                    if($type_payment == 'P'){
                        $client->points += $montant;
                    }
                    if($type_payment == 'C'){
                        // to do
                    }
                    $client->save();
                    return response()->json(array(
                        'code'      =>  200,
                        'message'   =>  'Paiment supprimé'
                    ), 200); 
                }else{
                    return response()->json(array(
                        'code'      =>  400,
                        'message'   =>  'Une erreur est survenu lors de la suppression du paiment '.$pid
                    ), 400); 
                }
            }else{
                return response()->json(array(
                    'code'      =>  403,
                    'message'   =>  'Ce paiment ne concerne pas cette vente.'
                ), 403); 
            }
        }else{
            return response()->json(array(
                'code'      =>  404,
                'message'   =>  'Le paiment n\'existe pas.'
            ), 404); 
        }
    }
}
