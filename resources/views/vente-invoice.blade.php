<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">

    <title>Facture</title>

    <!-- Bootstrap core CSS -->
    <!-- <link rel="stylesheet" type="text/css" href="{{ asset('css/style.css') }}" > -->
    <link rel="stylesheet" href="{{ ltrim(elixir('css/style.css'), '/') }}" />
    <!-- @yield('bootstrap') -->
    <style>
        .text-right {
            text-align: right;
        }
    </style>

</head>

<body class="login-page" style="background: white">

    <div>
        <div class="row">
            <div class="col-xs-7">
                <h4>De:</h4>
                <strong>{{$vente["agence"]["nom"]}}</strong><br>
                {{$vente["agence"]["addresse"]}} <br>
                <br>
            </div>

            <div class="col-xs-4">
                <!-- <img src="https://res.cloudinary.com/dqzxpn5db/image/upload/v1537151698/website/logo.png" alt="logo"> -->
                <h4>A l'ordre de: <strong>{{$vente["client"]["nom"]}}</strong></h4>
                <br>
                <br>
            </div>
        </div>

        <div style="margin-bottom: 0px">&nbsp;</div>

        <div class="row">
            <!-- <div class="col-xs-6">
                <h4>To:</h4>
                <address>
                    <strong>Andre Madarang</strong><br>
                    <span>andre@andre.com</span> <br>
                    <span>123 Address St.</span>
                </address>
            </div> -->

            <div class="col-md-6">
                <div class="row">
                    <div class="col-md-6">Facture Numéro: <b>{{$vente["id"]}}</b></div>
                    <div class="col-md-6 text-right"></div>
                </div>
                <div class="row">
                    <div class="col-md-6">Utilisateur: <b>{{$vente["user"]["name"]}}</b></div>
                    <div class="col-md-6 text-right"></div>
                </div>
                <div class="row">
                    <div class="col-md-6">Type: <b>{{$vente["type_vente"] == 'G' ? 'Gros' : 'Détails'}}</b></div>
                    <div class="col-md-6 text-right"></div>
                </div>
                <div class="row">
                    <div class="col-md-6">Date: <b>{{ date('d/m/yy', strtotime($vente["date"])) }}</b></div>
                    <div class="col-md-6 text-right"></div>
                </div>


                <div style="margin-bottom: 0px">&nbsp;</div>

                <!-- <table style="width: 100%; margin-bottom: 20px">
                    <tbody>
                        <tr class="well" style="padding: 5px">
                            <th style="padding: 5px"><div> Balance Due (CAD) </div></th>
                            <td style="padding: 5px" class="text-right"><strong> $600 </strong></td>
                        </tr>
                    </tbody>
                </table> -->
            </div>
        </div>


        <h4 class="text-info"><b><u>Produits vendus:</u></b></h4>
        <br>
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th class="text-center">Produit</th>
                    <th class="text-center">PU</th>
                    <th class="text-center">Quantité</th>
                    <th class="text-center">Remise</th>
                    <th class="text-center">Total par produit</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($vente["produits"] as $prod)
                <tr>
                    <td class="text-center">
                        {{$prod["nom"]}}
                    </td>
                    <td class="text-center">{{$prod["prix"]}}</td>
                    <td class="text-center">{{$prod["quantite"]}}</td>
                    <td class="text-center">{{$prod["remise"]}}</td>
                    <td class="text-center">{{$prod["remise"]!=0 ? $prod["prix"]*$prod["quantite"]*(1-$prod["remise"]/100) :
                        $prod["prix"]*$prod["quantite"]}}</td>
                </tr>
                @endforeach
            </tbody>
        </table>


        <div style="margin-bottom: 0px">&nbsp;</div>

        <h4 class="text-info"><b><u>Paiements:</u></b></h4>
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th class="text-center">Référence</th>
                    <th class="text-center">Montant(DH)</th>
                    <th class="text-center">Date</th>
                    <th class="text-center">Type</th>
                    <th class="text-center">N° chéque</th>
                    <th class="text-center">Utilisateur</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($vente["paiments"] as $p)
                <tr>
                    <td class="text-center">{{$p["id"]}}</td>
                    <td class="text-center">{{$p["montant"]}}</td>
                    <td class="text-center">{{date('d/m/yy', strtotime($p["created_at"]))}}</td>
                    @switch($p["type"])
                        @case('E')
                        <td class="text-center">Espèces</td>
                        @break
                        @case('H')
                        <td class="text-center">Chèque</td>
                        @case('P')
                        <td class="text-center">Points</td>
                        @case('C')
                        <td class="text-center">Crédit</td>
                        @break

                        @default
                        <td class="text-center">Espèces</td>
                    @endswitch
                    <td class="text-center">{{$p["numero_cheque"] ? numero_cheque : '-'}}</td>
                    <td class="text-center">{{$p["user"]["name"]}}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <h3>
            <b class="pull-right">Total:
              <span class="text-danger">{{$vente["montant"]}} DH</span>
            </b>
          </h3>

        <!-- <div class="row">
            <div class="col-xs-8 invbody-terms">
                Thank you for your business. <br>
                <br>
                <h4>Payment Terms</h4>
                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ad eius quia, aut doloremque, voluptatibus
                    quam ipsa sit sed enim nam dicta. Soluta eaque rem necessitatibus commodi, autem facilis iusto
                    impedit!</p>
            </div>
        </div> -->
    </div>

</body>

</html>