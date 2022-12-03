<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Illuminate\Support\Facades\Auth;

class PaimentCreditController extends Controller
{
    public function getCreditHistory($id){
        return \App\Credit::with([
            'paiments',
            "paiments.user:id,name"
        ])->find($id);
    }

    public function postCreditPayment(Request $req){
        $credit = \App\Credit::find($req->input('creditId'));
        $credits = \App\Credit::where('client_id',$credit->client_id)
                              ->where('agence_id', auth()->user()->agence_id)
                              ->where('statut','N')
                              ->get();
        $somme = \App\PaimentCredit::where('credit_id', $req->input('creditId'))->sum('montant');
        $sommeCredits = 0;
        foreach ($credits as $cr) {
            $sommeCredits += $cr->reste;
        }
        if(Auth::user()->role == "user" || (Auth::user()->role == "admin" && !($req->input('montant') + $somme > $credit->montant))){
            if($req->input('montant') + $somme > $credit->montant){
            return response()->json(array(
                 'code'      =>  501,
                 'message'   =>  "vous depasserez le montant du credit si vous continuez cette opération"
             ), 400);  
            }else{
                $new = new \App\PaimentCredit;
                $new->credit_id = $req->input('creditId');
                $new->user_id = Auth::user()->id;
                $new->montant = $req->input('montant');
                $new->type = $req->input('type');
                $new->checkNumber = $req->input('checkNumber');
                $new->save();
                if($credit->montant == $somme + $req->input('montant')){
                    $credit->statut = 'P';
                    $credit->reste = 0;
                }else{
                    $credit->statut = 'N';
                    $credit->reste -= $req->input('montant');
                }
                $credit->save();
                return $new;
            }
        }else{
            if($sommeCredits < $req->input('montant')){
               return response()->json(array(
                 'code'      =>  501,
                 'message'   =>  "vous depasserez le montant du credit si vous continuez cette opération"
             ), 400); 
           }else{
                $diff = $req->input('montant');
                foreach ($credits as $cr) {
                    if($diff > 0){
                        $new = new \App\PaimentCredit;
                        $new->credit_id = $cr->id;
                        $new->user_id = Auth::user()->id;
                        $new->type = $req->input('type');
                        $new->checkNumber = $req->input('checkNumber');
                        if($cr->reste > $diff){
                            $new->montant = $diff;
                            $cr->reste -= $diff;
                            $cr->statut = 'N';
                            $diff = 0;
                        }else{
                            $new->montant = $cr->reste;
                            $diff -= $cr->reste;
                            $cr->reste = 0;
                            $cr->statut = 'P';
                        }
                        $new->save();
                        $cr->save();
                    }
                }
                return response()->json(array(
                 'code'      =>  200,
                 'message'   =>  "done"
                ), 200); 
           }
        }
    }

    public function deleteCreditPayment($id){
        $new = \App\PaimentCredit::find($id);
        $credit = \App\Credit::find($new->credit_id);
    	if($new->delete()){
            $credit->statut = 'N';
            $credit->reste += $new->montant;
            $credit->save();
            return response()->json(array(
                 'code'      =>  200,
                 'message'   =>  "paiement supprimé"
             ), 200);  
        }
        return response()->json(array(
                 'code'      =>  200,
                 'message'   =>  "erreur"
        ), 200);  
    }
}