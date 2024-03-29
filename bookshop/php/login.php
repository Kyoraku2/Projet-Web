<?php
/* ------------------------------------------------------------------------------
    Architecture de la page
    - étape 1 : vérifications diverses
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
- vérifications diverses
------------------------------------------------------------------------------*/

// si utilisateur déjà authentifié, on le redirige vers la page index.php
if (at_est_authentifie()){
    $page = isset($_POST['destination']) ? $_POST['destination'] : '../index.php';
    header("Location: $page");
    exit();
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


// ----------  Fonctions locales du script ----------- //

/**
 * Affichage du contenu de la page (formulaire de connexion)
 *
 * @param   array   $err    tableau d'erreurs à afficher
 * @global  array   $_POST
 */
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
    echo    
        '<p>Pour vous connecter, merci de fournir les informations suivantes. </p>',
        '<form method="post" action="login.php">';
        if(isset($_POST['destination'])){
            $page=$_POST['destination'];
        }else{
            $page=isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';
        }
            echo '<input type="hidden" name="destination" value="',$page,'"/>',
            '<table>';
    at_aff_ligne_input('Votre adresse email :', array('type' => 'email', 'name' => 'email', 'value' => $email, 'required' => false));
    at_aff_ligne_input('Choisissez un mot de passe :', array('type' => 'password', 'name' => 'passe', 'value' => '', 'required' => false));

    echo 
                '<tr>',
                    '<td colspan="2">',
                        '<input type="submit" name="btnConnect" value="Se connecter">',
                        '<input type="reset" value="Réinitialiser">', 
                    '</td>',
                '</tr>',
            '</table>',
        '</form>',
        '<form method="post" action="inscription.php">',
            '<input type="hidden" name="destination" value="',$page,'"/>',
            '<table>',
                '<tr>',
                    '<td>Vous n\'êtes encore inscrit ?</td>',
                    '<td><input type="submit" name="btnInscription" value="Inscrivez vous" style="width:130px;"></td>',
                '</tr>',
            '</table>',
        '</form>';
        ;
}


/**
 *  Traitement de la connexion 
 *
 *      Etape 1. vérification de la validité des données
 *                  -> return des erreurs si on en trouve
 *      Etape 2. identification de la session
 *
 * Toutes les erreurs détectées qui nécessitent une modification du code HTML sont considérées comme des tentatives de piratage 
 * et donc entraînent l'appel de la fonction at_session_exit() sauf les éventuelles suppressions des attributs required 
 * car l'attribut required est une nouveauté apparue dans la version HTML5 et nous souhaitons que l'application fonctionne également 
 * correctement sur les vieux navigateurs qui ne supportent pas encore HTML5
 *
 * @global array    $_POST
 *
 * @return array    tableau assosiatif contenant les erreurs
 */
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
        }else{ 
            $row=mysqli_fetch_assoc($res);
            $bdpasse=$row['cliPassword'];
            $id=$row['cliID'];
            if(!password_verify($passe,$bdpasse)){
                $erreurs[] = 'Erreur dans les identifiants saisis.';
            }
        }
        // libération des ressources 
        mysqli_free_result($res);
        mysqli_close($bd);
    }
    
    // s'il y a des erreurs ==> on retourne le tableau d'erreurs    
    if (count($erreurs) > 0) {  
        return $erreurs;    
    }

    // mémorisation de l'ID dans une variable de session 
    // cette variable de session permet de savoir si le client est authentifié
    $_SESSION['id'] = $id;
}

?>