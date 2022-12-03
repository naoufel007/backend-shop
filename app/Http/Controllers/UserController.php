<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function getClients()
    {
        return \App\Client::all();
    }

    public function getUsers()
    {

        $month = Carbon::now()->month;
        $year = Carbon::now()->year;
        if(request()->has('date')){
            $date = explode('-', request()->get("date"));
            $month = $date[0];
            $year = $date[1];
        }
          
        if (request()->has('keyword')) {
            $keyword = request()->get("keyword");

            if (Auth::user()->role == "user") {
                $list = \App\User::with([
                    "agence:id,nom",
                ])
                    ->where('id', auth::user()->id)
                    ->get();

                $sums = DB::table('users')
                ->join('commissions', 'users.id', '=', 'commissions.user_id')
                ->select('users.name', DB::raw('SUM(commissions.commission) AS com'))
                ->whereMonth('commissions.created_at', '=', $month)
                ->whereYear('commissions.created_at', '=', $year)
                ->groupBy('users.name')
                ->get();
                
                foreach($list as $l){
                    $l['commission_sum'] = [];
                    foreach($sums as $sum){
                        if($l['name'] == $sum->name){
                            $l['commission_sum'] = [
                                [
                                    "user_id" => $l['id'],
                                    "commission" => $sum->com
                                ]
                            ];
                         
                        }
                    }
                }

                return $list;
            }

            $list =  \App\User::with([
                "agence:id,nom",
            ])
                ->where('name', 'like', '%' . $keyword . '%')
                ->orWhere('telephone', 'like', '%' . $keyword . '%')
                ->orWhere('cin', 'like', '%' . $keyword . '%')
                ->get();
            
                $sums = DB::table('users')
                ->join('commissions', 'users.id', '=', 'commissions.user_id')
                ->select('users.name', DB::raw('SUM(commissions.commission) AS com'))
                ->whereMonth('commissions.created_at', '=', $month)
                ->whereYear('commissions.created_at', '=', $year)
                ->groupBy('users.name')
                ->get();
                
                foreach($list as $l){
                    $l['commission_sum'] = [];
                    foreach($sums as $sum){
                        if($l['name'] == $sum->name){
                            $l['commission_sum'] = [
                                [
                                    "user_id" => $l['id'],
                                    "commission" => $sum->com
                                ]
                            ];
                         
                        }
                    }
                }

                return $list;
        }

        if (Auth::user()->role == "user") {
            $list = \App\User::with([
                "agence:id,nom",
            ])
                ->where('id', auth::user()->id)
                ->get();
                $sums = DB::table('users')
                ->join('commissions', 'users.id', '=', 'commissions.user_id')
                ->select('users.name', DB::raw('SUM(commissions.commission) AS com'))
                ->whereMonth('commissions.created_at', '=', $month)
                ->whereYear('commissions.created_at', '=', $year)
                ->groupBy('users.name')
                ->get();
                
                foreach($list as $l){
                    $l['commission_sum'] = [];
                    foreach($sums as $sum){
                        if($l['name'] == $sum->name){
                            $l['commission_sum'] = [
                                [
                                    "user_id" => $l['id'],
                                    "commission" => $sum->com
                                ]
                            ];
                         
                        }
                    }
                }

                return $list;
        }
        $list = \App\User::with([
            "agence:id,nom",
        ])->get();

        $sums = DB::table('users')
                ->join('commissions', 'users.id', '=', 'commissions.user_id')
                ->select('users.name', DB::raw('SUM(commissions.commission) AS com'))
                ->whereMonth('commissions.created_at', '=', $month)
                ->whereYear('commissions.created_at', '=', $year)
                ->groupBy('users.name')
                ->get();
                
                foreach($list as $l){
                    $l['commission_sum'] = [];
                    foreach($sums as $sum){
                        //var_dump($sum);
                        if($l['name'] == $sum->name){
                            $l['commission_sum'] = [
                                [
                                    "user_id" => $l['id'],
                                    "commission" => $sum->com
                                ]
                            ];
                        }
                    }
                }

                return $list;
    }

    public function getUserById($id)
    {

        return \App\User::with([
            "agence:id,nom",
            "commissions.produit:id,nom",
            "commissions.vente.client:id,nom",
            "commissions.vente.produits",
        ])->where("id", $id)->first();
    }

    public function getUserByCIN($cin, $self_cin = "")
    {
        return \App\User::where([
            ["cin", "=", $cin],
            ["cin", "!=", $self_cin],
        ])->get();
    }
    public function postUser(Request $request)
    {
        $cin = $request->input('cin');
        $user = $this->getUserByCIN($cin);

        if (count($user) != 0) {
            return response()->json(array(
                'code' => 403,
                'message' => 'CIN déjà existant',
            ), 403);
        }
        $newUser = new \App\User;
        $newUser->name = $request->input('name');
        $newUser->cin = $request->input('cin');
        $newUser->addresse = $request->input('addresse');
        $newUser->agence_id = $request->input('agence_id');
        $newUser->telephone = $request->input('telephone');
        $newUser->p_casio_achat = $request->input('pourcentageAchatCasio');
        $newUser->p_casio_vente = $request->input('pourcentageVenteCasio');
        $newUser->p_service = $request->input('pourcentageService');
        $newUser->password = bcrypt($request->input('password'));
        $newUser->pass = $request->input('password');
        $newUser->save();
        $intputPourcentages = $request->input("pourcentages");
        $pourcentages = [];
        if ($intputPourcentages) {

            $sizeOfPourcentage = count($intputPourcentages);
            for ($i = 0; $i < $sizeOfPourcentage; $i++) {
                $pourcentages[] = new \App\Pourcentage([
                    "user_id" => $newUser->id,
                    "produit_id" => $intputPourcentages[$i]["produitId"],
                    "pourcentage" => $intputPourcentages[$i]["pourcentage"],
                ]);
            }
            $newUser->pourcentages()->saveMany($pourcentages);
        }

        return $newUser;
    }

    public function putUser(Request $request)
    {
        $cin = $request->input('cin');

        $newUser = \App\User::find($request->input('id'));
        if (!$newUser) {
            return response()->json(array(
                'code' => 403,
                'message' => "le client n'existe pas.",
            ), 404);
        }
        $client = $this->getUserByCIN($cin, $newUser->cin);
        if (count($client) != 0) {
            return response()->json(array(
                'code' => 403,
                'message' => 'CIN déjà existant',
            ), 403);
        }
        $newUser->name = $request->input('name');
        $newUser->cin = $request->input('cin');
        $newUser->telephone = $request->input('telephone');
        $newUser->addresse = $request->input('addresse');
        $newUser->p_casio_achat = $request->input('pourcentageAchatCasio');
        $newUser->p_casio_vente = $request->input('pourcentageVenteCasio');
        $newUser->p_service = $request->input('pourcentageService');
        $newUser->agence_id = $request->input('agence_id');
        $newUser->pass = $request->input('password');
        $newUser->password = bcrypt($request->input('password'));
        $newUser->save();
        return $newUser;
    }

    public function getPaymentVenteCheque()
    {
        $queryBuilder = DB::table('paiment_ventes')
            ->join('ventes', 'ventes.id', 'paiment_ventes.vente_id')
            ->join('clients', 'clients.id', 'ventes.client_id')
            ->select('clients.nom', 'paiment_ventes.numero_cheque as checkNumber', 'paiment_ventes.montant',
                'paiment_ventes.created_at as date')
            ->where('type', 'H');

        if (request()->has('checkNumber')) {
            $queryBuilder->where('paiment_ventes.numero_cheque', request()->get('checkNumber'));
        }
        $paginator = $queryBuilder->paginate(20);
        $chequeVentes = $paginator->items();

        return [
            "checks" => $chequeVentes,
            "total" => $paginator->total(),
            "currentPage" => $paginator->currentPage(),
            "itemsPerPage" => 20,
            "displayPagination" => $paginator->total() > 20,
        ];
    }

    public function getPaymentCreditCheque()
    {
        $queryBuilder = DB::table('paiment_credits')
            ->join('credits', 'credits.id', 'paiment_credits.credit_id')
            ->join('clients', 'clients.id', 'credits.client_id')
            ->select('clients.nom', 'paiment_credits.checkNumber', 'paiment_credits.montant',
                'paiment_credits.created_at as date')
            ->where('type', 'H');
        $paginator = $queryBuilder->paginate(20);
        $chequeCredits = $paginator->items();

        return [
            "checks" => $chequeCredits,
            "total" => $paginator->total(),
            "currentPage" => $paginator->currentPage(),
            "itemsPerPage" => 20,
            "displayPagination" => $paginator->total() > 20,
        ];
    }
}
