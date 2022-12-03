<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ChargeController extends Controller
{
    public function postCharge(Request $request, $agenceId){
        $newCharge = new  \App\Charge;
        $newCharge->montant = $request->input('montant');
        $newCharge->agence_id = $agenceId;
        $newCharge->type = $request->type;
        $newCharge->save(); 
        return $newCharge;
    }

    public function getCaisse($agenceId){
        $list = [];
        $list['achatTotal'] = DB::table('achats')->where('agence_id', $agenceId)->sum('montant');
        $list['venteTotal'] = DB::table('ventes')->where('agence_id', $agenceId)->sum('montant');
        $list['serviceTotal'] = DB::table('services')->where('agence_id', $agenceId)->sum('montant');
        $list['retourTotal'] = DB::table('retours')->where('agence_id', $agenceId)->sum('montant');
        $list['capital'] = DB::table('agences')->where('id', $agenceId)->value('capital');
        $list['charges'] = \App\Charge::where('agence_id', $agenceId)->get();
        return $list;
    }

    public function updateCharge(Request $request)
    {
        $newCharge = \App\Charge::find($request->input('id'));
        $newCharge->montant = $request->input('montant');
        $newCharge->agence_id = $request->input('agence_id');
        $newCharge->type = $request->type;
        $newCharge->update();
        return $newCharge;
    }

    public function getCharge($id){
        return \App\Charge::find($id);
    }
}
