<?php

namespace App\Http\Controllers;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Agence extends Controller
{
    public function getAgences(){
    	$list = [];
      $from = Carbon::now();
      $to = Carbon::now()->addDays(1);
      if(request()->has('dateFrom')){
        $from = Carbon::parse(request()->get("dateFrom"));
      }
      if(request()->has('dateTo')){
        $to = Carbon::parse(request()->get("dateTo"));
      }
        
		if(Auth::user()->role == "user"){
			$agence = \App\Agence::find(Auth::user()->agence_id);
			$user = \App\User::find(Auth::user()->id);

            $totalPrixVente = DB::table('paiment_ventes')->where('user_id', Auth::user()->id)
                                                         ->whereIn('type',['E','H'])
              											                     ->whereBetween('created_at', [$from->toDateString(),$to->toDateString()])
            											                       ->sum('montant');

            $totalCredit = DB::table('paiment_credits')->where('user_id', Auth::user()->id)
              											                   ->whereBetween('created_at', [$from->toDateString(),$to->toDateString()])
            											                     ->sum('montant');
            $totalRetour = \App\Retour::where('user_id',Auth::user()->id)
                                      ->whereBetween('created_at', [$from->toDateString(),$to->toDateString()])
                                      ->sum('montant');

            $totalAchat = \App\Achat::where('user_id', Auth::user()->id)
                                      ->whereBetween('created_at', [$from->toDateString(),$to->toDateString()])
                                      ->sum('montant');
            $totalService = \App\Service::where('user_id', Auth::user()->id)
                                      ->whereBetween('created_at', [$from->toDateString(),$to->toDateString()])
                                      ->sum('montant');
            $l = [];                         
            $gain = \App\Vente::where('agence_id', Auth::user()->agence_id)
                              ->whereBetween('created_at', [$from->toDateString(),$to->toDateString()])
                              ->sum('gain');
            $l['gain'] = $gain;    
            
            $l['agence'] = $agence;
            $userList = [];
            $oneUser = [];
            $oneUser['user'] = $user;
            $oneUser['totalPrixVente'] = $totalPrixVente;
            $oneUser['totalCredit'] = $totalCredit;
            $oneUser['totalRetour'] = $totalRetour;
            $oneUser['totalAchat'] = $totalAchat;
            $oneUser['totalService'] = $totalService;
            array_push($userList, $oneUser);
            $l['userList'] = $userList;
            array_push($list, $l);
			return $list;
		}
		$agences = \App\Agence::all();
		foreach ($agences as $agence) {
      $l = [];
      $gain = \App\Vente::where('agence_id', $agence->id)
                ->whereBetween('created_at', [$from->toDateString(),$to->toDateString()])
                ->sum('gain');
      $l['gain'] = $gain; 

			$l['agence'] = $agence;
			$userList = [];
			$users = \App\User::where('agence_id',$agence->id)->get();
			foreach ($users as $user) {
				$oneUser = [];
				$oneUser['user'] = $user;

            	$totalPrixVente = DB::table('paiment_ventes')->where('user_id', $user->id)
                                                         	 ->whereIn('type',['E','H'])
              											 	 ->whereBetween('created_at', [$from->toDateString(),$to->toDateString()])
            											 	 ->sum('montant');

            	$totalCredit = DB::table('paiment_credits')->where('user_id', $user->id)
              											   ->whereBetween('created_at', [$from->toDateString(),$to->toDateString()])
            											   ->sum('montant');
            	$totalRetour = \App\Retour::where('user_id', $user->id)
                                          ->whereBetween('created_at', [$from->toDateString(),$to->toDateString()])
                                          ->sum('montant');
              $totalAchat = \App\Achat::where('user_id', $user->id)
                                        ->whereBetween('created_at', [$from->toDateString(),$to->toDateString()])
                                        ->sum('montant');
              $totalService = \App\Service::where('user_id', $user->id)
                                        ->whereBetween('created_at', [$from->toDateString(),$to->toDateString()])
                                        ->sum('montant');
            	$oneUser['totalPrixVente'] = $totalPrixVente;
            	$oneUser['totalCredit'] = $totalCredit;
            	$oneUser['totalRetour'] = $totalRetour;
              $oneUser['totalAchat'] = $totalAchat;
              $oneUser['totalService'] = $totalService;
				array_push($userList, $oneUser);
			} 
			$l['userList'] = $userList;
			array_push($list, $l);
		}
		return $list;
    }

    public function getAgencesClients(){
    	return \App\Agence::all();
    }

    public function getStatisticsByAgenceId($agenceId, $month, $year){
      $list = []; $produits = []; $employees = []; $mouvements = [];

      //produits
      $plus = [];
      $moins = [];
      $produits_ventes_plus = DB::table('ventes')->where('ventes.agence_id', $agenceId)
                                   ->whereMonth('ventes.created_at', '=', $month)
                                   ->whereYear('ventes.created_at', '=', $year)
                                   ->join('vente_produit', 'ventes.id', '=', 'vente_produit.vente_id')
                                   ->join('produits', 'vente_produit.produit_id', '=', 'produits.id')
                                   ->select('produits.nom', DB::raw('COUNT(vente_produit.produit_id) AS count'))
                                   ->groupBy('produits.nom')
                                   ->orderBy('count','desc') 
                                   ->limit(10)
                                   ->get();
      $produits_ventes_moins = DB::table('ventes')->where('ventes.agence_id', $agenceId)
                                   ->whereMonth('ventes.created_at', '=', $month)
                                   ->whereYear('ventes.created_at', '=', $year)
                                   ->join('vente_produit', 'ventes.id', '=', 'vente_produit.vente_id')
                                   ->join('produits', 'vente_produit.produit_id', '=', 'produits.id')
                                   ->select('produits.nom', DB::raw('COUNT(vente_produit.produit_id) AS count'))
                                   ->groupBy('produits.nom')
                                   ->orderBy('count','asc') 
                                   ->limit(10)
                                   ->get();
      $plusListData = []; $plusListLabels = [];
      foreach ($produits_ventes_plus as $produit) {
        $plusListLabels[] = $produit->nom;
        $plusListData[] = $produit->count;
      }
      $plus['data'] = $plusListData; $plus['labels'] = $plusListLabels;
      $moinsListData = []; $moinsListLabels = [];
      foreach ($produits_ventes_moins as $produit) {
        $moinsListLabels[] = $produit->nom;
        $moinsListData[] = $produit->count;
      }
      $moins['data'] = $moinsListData; $moins['labels'] = $moinsListLabels;
      $produits["plus"] = $plus; $produits["moins"] = $moins; 
      $list["produits"] = $produits;

      //Employees
      $employee_commission = DB::table('users')->where('users.agence_id', $agenceId)
                                   ->join('commissions', 'users.id', '=', 'commissions.user_id')
                                   ->whereMonth('commissions.created_at', '=', $month)
                                   ->whereYear('commissions.created_at', '=', $year)                                   
                                   ->select('users.name', DB::raw('SUM(commissions.commission) AS sum'))
                                   ->groupBy('users.name')
                                   ->get();
      $employeeListData = []; $employeeListLabels = [];
      foreach ($employee_commission as $employee) {
        $employeeListLabels[] = $employee->name;
        $employeeListData[] = $employee->sum;
      }
      $employees['data'] = $employeeListData; $employees['labels'] = $employeeListLabels;
      $list["employees"] = $employees;   

      //mouvements
      $mouvementsListData = []; $mouvementsListLabels = ["Achats", "Ventes", "Services", "Retours", "Charges"];
      $mouvementsListData[] = DB::table('achats')->where('agence_id', $agenceId)->sum('montant');
      $mouvementsListData[] = DB::table('ventes')->where('agence_id', $agenceId)->sum('montant');
      $mouvementsListData[] = DB::table('services')->where('agence_id', $agenceId)->sum('montant');
      $mouvementsListData[] = DB::table('retours')->where('agence_id', $agenceId)->sum('montant');
      $mouvementsListData[] = DB::table('charges')->where('agence_id', $agenceId)->sum('montant');
      $mouvements['data'] = $mouvementsListData; $mouvements['labels'] = $mouvementsListLabels;
      $list["mouvements"] = $mouvements; 

      return $list;
    }
}
