<?php
/* ------------------------------------------------------------------------------
    Architecture de la page
    - étape 1 : vérifications diverses et traitement des soumissions
    - étape 2 : génération du code HTML de la page
------------------------------------------------------------------------------*/

ob_start(); //démarre la bufferisation
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_bookshop.php';

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)

// traitement si soumission du formulaire d'inscription
$err = isset($_POST['btnConnect']) ? atl_traitement_connexion() : array(); 

/*------------------------- Etape 1 --------------------------------------------
- vérifications diverses et traitement des soumissions
------------------------------------------------------------------------------*/

// si utilisateur déjà authentifié, on le redirige vers la page index.php
if (at_est_authentifie()){
    if(isset($_REQUEST["destination"])){
        header("Location: {$_REQUEST["destination"]}");   
    }else{
        header('Location: ../index.php');
    }
}

/*------------------------- Etape 2 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

at_aff_debut('BookShop | Connexion', '../styles/bookshop.css', 'main');

at_aff_enseigne_entete();

atl_aff_contenu($err);

at_aff_pied();

at_aff_fin('main');

ob_end_flush();

function atl_aff_contenu($err) {
    // réaffichage des données soumises en cas d'erreur, sauf les mots de passe
    $email = isset($_POST['email']) ? at_html_proteger_sortie(trim($_POST['email'])) : '';

    echo 
        '<h1>Connexion à BookShop</h1>';
        
    if (count($err) > 0) {
        echo '<p class="error">Vous n\'avez pas pu vous connecter e à cause des erreurs suivantes : ';
        foreach ($err as $v) {
            echo '<br> - ', $v;
        }
        echo '</p>';    
    }
    $url=$_SERVER['HTTP_REFERER']; // ici ça casse tout sur validator
    echo    
        '<p>Pour vous connecter, merci de fournir les informations suivantes. </p>',
        '<form method="post" action="login.php">',
            '<input type="hidden" name="destination" value="',$url,'">',
            '<table>';
    at_aff_ligne_input('Votre adresse email :', array('type' => 'email', 'name' => 'email', 'value' => $email, 'required' => false));
    at_aff_ligne_input('Choisissez un mot de passe :', array('type' => 'password', 'name' => 'passe', 'value' => '', 'required' => false));

    echo 
                '<tr>',
                    '<td>Vous n\'êtes encore inscrit ?</td>',
                    '<td><a href="./inscription.php">Inscrivez vous !</a></td>',
                '</tr>',
                '<tr>',
                    '<td colspan="2">',
                        '<input type="submit" name="btnConnect" value="Se connecter">',
                        '<input type="reset" value="Réinitialiser">', 
                    '</td>',
                '</tr>',
            '</table>',
        '</form>';
}

function atl_traitement_connexion() {
    if( !at_parametres_controle('post', array('email','passe','btnConnect','destination'))) {
        at_session_exit();   
    }

    $erreurs = array();
    
    // vérification du format de l'adresse email
    $email = trim($_POST['email']);
    if (empty($email)){
        $erreurs[] = 'L\'adresse mail ne doit pas être vide.'; 
    }else {
        if (mb_strlen($email, 'UTF-8') > LMAX_EMAIL){
            $erreurs[] = 'L\'adresse mail ne peut pas dépasser '.LMAX_EMAIL.' caractères.';
        }
        // la validation faite par le navigateur en utilisant le type email pour l'élément HTML input
        // est moins forte que celle faite ci-dessous avec la fonction filter_var()
        // Exemple : 'l@i' passe la validation faite par le navigateur et ne passe pas
        // celle faite ci-dessous
        if(! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreurs[] = 'L\'adresse mail n\'est pas valide.';
        }
    }

    // vérification de mot de passe
    $passe = trim($_POST['passe']);
    $nb = mb_strlen($passe, 'UTF-8');
    if ($nb < LMIN_PASSWORD || $nb > LMAX_PASSWORD){
        $erreurs[] = 'Le mot de passe doit être constitué de '. LMIN_PASSWORD . ' à ' . LMAX_PASSWORD . ' caractères.';
    }

    $id=0;
    if (count($erreurs) == 0) {
        // vérification de l'unicité de l'adresse email 
        // (uniquement si pas d'autres erreurs, parce que ça coûte un bras)
        $bd = at_bd_connecter();

        // pas utile, car l'adresse a déjà été vérifiée, mais tellement plus sécurisant...
        $email = at_bd_proteger_entree($bd, $email);
        $passe = at_bd_proteger_entree($bd, $passe);
        $sql = "SELECT cliID,cliPassword FROM clients WHERE cliEmail = '$email'"; 
    
        $res = mysqli_query($bd,$sql) or at_bd_erreur($bd,$sql);
        
        if (mysqli_num_rows($res) == 0) {
            $erreurs[] = 'Erreur dans les identifiants saisis.';
            // libération des ressources 
            mysqli_free_result($res);
            mysqli_close($bd);
        }else{ 
            $row=mysqli_fetch_assoc($res);
            $bdpasse=$row['cliPassword'];
            $id=$row['cliID'];
            if(!password_verify($passe,$bdpasse)){
                $erreurs[] = 'Erreur dans les identifiants saisis.';
                mysqli_free_result($res);
                mysqli_close($bd);
            }
            // libération des ressources 
            mysqli_free_result($res);
        }
        
    }
    
    // s'il y a des erreurs ==> on retourne le tableau d'erreurs    
    if (count($erreurs) > 0) {  
        return $erreurs;    
    }

    // mémorisation de l'ID dans une variable de session 
    // cette variable de session permet de savoir si le client est authentifié
    $_SESSION['id'] = $id;
    mysqli_close($bd);
}

?>