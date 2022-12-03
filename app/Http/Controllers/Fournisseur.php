<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
class Fournisseur extends Controller
{
    public function getFournisseurs(){
        /*
        if(Auth::user()->role == "user"){
            $idsArray = DB::table('agence_fournisseur')->select('fournisseur_id')->where('agence_id',Auth::user()->agence_id);

            return \App\Fournisseur::with(['agences:agence_id,nom'])
            ->whereIn('id', $idsArray)
            ->get();
        }

        return \App\Fournisseur::with(['agences:agence_id,nom'])->get();
        */
        $idsArray = DB::table('agence_fournisseur')->select('fournisseur_id')->where('agence_id',Auth::user()->agence_id);
        $fournisseurs = \App\Fournisseur::with(['agences:agence_id,nom'])
            ->whereIn('id', $idsArray)
            ->get();
        
        foreach ($fournisseurs as $fr) {
            $totalAchats = \App\Achat::where('fournisseur_id', $fr->id)->sum('montant');
            $paiments = \App\Paiment::where('fournisseur_id', $fr->id)->sum('montant');
            $fr['reste'] = $totalAchats-$paiments;
        }
        return $fournisseurs;    
    }

    public function postFournisseur(Request $request){
        $newF = new  \App\Fournisseur;
        $newF->name = $request->input('name');
        $newF->telephone = $request->input('telephone');
        $newF->fax = $request->input('fax');
        $newF->adresse = $request->input('adresse');
        $newF->save();
        $ids = [];
        $ids[] = Auth::user()->agence_id;
        $newF->agences()->attach($ids);
        return $newF;
    }

    public function deleteFournisseur($id){
        $four = \App\Fournisseur::find($id);
        if($four){
            $four->agences()->detach();
            if($four->delete()){
                return response()->json(array(
                    'code'      =>  200,
                    'message'   =>  "Le fournisseur a été supprimé"
                ), 200);  
                 
            }else
                return response()->json(array(
                    'code'      =>  404,
                    'message'   =>  'Le fournisseur n\'existe pas.'
                ), 404); 
            
        }
         return response()->json(array(
                    'code'      =>  404,
                    'message'   =>  'Le fournisseur n\'existe pas.'
                ), 404); 
    }

    public function getFournisseur($id){
        return \App\Fournisseur::with([
            'agences:agence_id,nom',
            'paiments',
            'paiments.user:id,name',
            'achats',
            'achats.user:id,name',
            'achats.agence:id,nom'
        ])->find($id);
    }

     public function updateFournisseur(Request $request){
        $four = \App\Fournisseur::find($request->input('id'));
        $four->name = $request->input('name');
        $four->telephone = $request->input('telephone');
        $four->fax = $request->input('fax');
        $four->adresse = $request->input('adresse');
        $four->save();
        return $four;
    }


    public function deletePaiment($fid,$pid)
    {
        $paiment = \App\Paiment::find($pid);
        if($paiment){
            if($paiment->fournisseur_id == $fid){
                if($paiment->delete()){
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
                    'message'   =>  'Ce paiment ne concerne pas ce fournisseur.'
                ), 403); 
            }
        }else{
            return response()->json(array(
                'code'      =>  404,
                'message'   =>  'Le paiment n\'existe pas.'
            ), 404); 
        }
    }

    public function addPaiment($fid, Request $request)
    {
        $four = \App\Fournisseur::find($fid);

        if($four){
            $montant = $request->input('montant');
            $type = $request->input('type');
            if($montant){
                $paiment = new \App\Paiment;
                $paiment->montant = $montant;
                $paiment->type = $type ? $type : 'PAIMENT';
                $paiment->fournisseur_id = $fid;
                $paiment->user_id = auth()->user()->id;
                 if($paiment->save()){
                     $paiment->user;// fetches the user of the currently created paiment.
                     return $paiment;
                 }else{
                     return response()->json(array(
                         '   code'      =>  404,
                         'message'   =>  'Il faut saisir un montant pour ajouter un paiment.'
                     ), 500); 
                 }
            }else{
                return response()->json(array(
                    'code'      =>  404,
                    'message'   =>  'Il faut saisir un montant pour ajouter un paiment.'
                ), 400); 
            }
        }else{
            return response()->json(array(
                'code'      =>  404,
                'message'   =>  'Le fournisseur n\'existe pas.'
            ), 404); 
        }
    }
}


