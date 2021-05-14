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

/*------------------------- Etape 1 --------------------------------------------
- vérifications diverses et traitement des soumissions
------------------------------------------------------------------------------*/
if (!at_est_authentifie()){
    $page = isset($_POST['destination']) ? $_POST['destination'] : '../index.php';
    header("Location: $page");
    exit();
}
/*------------------------- Etape 2 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

$bd = at_bd_connecter();

    $sql = "SELECT cliEmail,cliNomPrenom,cliPassword,cliAdresse,cliVille,cliCP,cliPays FROM clients WHERE cliID=".$_SESSION['id'];
    $res = mysqli_query($bd, $sql) or at_bd_erreur($bd, $sql);
    $t = mysqli_fetch_assoc($res);

at_aff_debut('BookShop | Inscription', '../styles/bookshop.css', 'main');

at_aff_enseigne_entete();

if(!isset($_POST['modif'])){
    atl_aff_contenu($t);
}else{
    $err = (isset($_POST['currpass'])) ? atl_traitement_connexion($t,$bd) : array();
    atl_aff_contenu2($t,$err);
}


at_aff_pied();

at_aff_fin('main');

ob_end_flush();


// ----------  Fonctions locales du script ----------- //

function atl_aff_contenu($t){
    echo '<h1>Compte Utilisateur</h1>';
    echo '<form method="post" action="compte.php">',
        '<table>';
        atl_aff_ligne("Email",$t['cliEmail']);
        atl_aff_ligne("Nom et Prénom",$t['cliNomPrenom']);
        atl_aff_ligne("Mot de passe",$t['cliPassword']);
        atl_aff_ligne("Adresse",$t['cliAdresse']);
        atl_aff_ligne("Ville",$t['cliVille']);
        atl_aff_ligne("Code Postal",$t['cliCP']);
        atl_aff_ligne("Pays",$t['cliPays']);
    echo '<tr>',
    '<td colspan="2"><input type="submit" name="modif" value="Modifier les informations" style="width:175px;background-size: 175px 26px"></td>',
    '</tr>',
    '</table>',
    '</form>';
}

function atl_aff_contenu2($t,$err){

    $email = at_html_proteger_sortie(trim($t['cliEmail']));
    $nomprenom = at_html_proteger_sortie(trim($t['cliNomPrenom']));
    $adresse = at_html_proteger_sortie(trim($t['cliAdresse']));
    $ville = at_html_proteger_sortie(trim($t['cliVille']));
    $codePostal = at_html_proteger_sortie(trim($t['cliCP']));
    $pays = at_html_proteger_sortie(trim($t['cliPays']));
    echo '<h1>Compte Utilisateur</h1>';

    if (count($err) > 0) {
        echo '<p class="error">Votre inscription n\'a pas pu être réalisée à cause des erreurs suivantes : ';
        foreach ($err as $v) {
            echo '<br> - ', $v;
        }
        echo '</p>';    
    }


    echo '<form method="post" action="compte.php">',
        '<table>';
    at_aff_ligne_input('Email :', array('type' => 'email', 'name' => 'email', 'value' => $email, 'required' => false));
    at_aff_ligne_input('Nouveau mot de passe (laisser vide si pas de modification) :', array('type' => 'password', 'name' => 'newpass', 'value' => ''));
    at_aff_ligne_input('Nom et prénom :', array('type' => 'text', 'name' => 'nomprenom', 'value' => $nomprenom, 'required' => false));
    at_aff_ligne_input('Adresse :', array('type' => 'text', 'name' => 'adresse', 'value' => $adresse));
    at_aff_ligne_input('Ville :', array('type' => 'text', 'name' => 'ville', 'value' => $ville));
    at_aff_ligne_input('Code Postal :', array('type' => 'number', 'name' => 'codePostal', 'value' => $codePostal));
    at_aff_ligne_input('Pays :', array('type' => 'text', 'name' => 'pays', 'value' => $pays));
    at_aff_ligne_input('Rentrez votre mot de passe actuel :', array('type' => 'password', 'name' => 'currpass', 'value' => '', 'required' => false));        
    echo '<tr>',
    '<td colspan="2">
    <input type="submit" name="modif" value="Valider">',
    '<input type="reset" value="Réinitialiser">',
    '</td>',
    
    '</tr>',
    '</table>',
    '</form>';
}

function atl_aff_ligne($nom,$valeur){
    if($nom === "Mot de passe"){
        $valeur = "<strong>Mot de passe caché</strong>";
    }
    echo "<tr>",
    "<td>$nom :</td>",
    "<td>$valeur</td>";
}

function atl_traitement_connexion($t,$bd) {

    if( !at_parametres_controle('post', array('email', 'nomprenom', 'newpass','currpass','adresse',
                                                'ville','pays','codePostal','modif'))) {
        at_session_exit();   
    }
    
    $erreurs = array();
    $id = $_SESSION['id'];
    
    // vérification du format de l'adresse email
    $email = trim($_POST['email']);
    if (empty($email)){
        $erreurs[] = 'L\'adresse mail ne doit pas être vide.'; 
    }
    else {
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
    
    // vérification des mots de passe
    $newpass = trim($_POST['newpass']);
    $currpass = trim($_POST['currpass']);
    if (empty($currpass)){
        $erreurs[] = 'Vous devez rentrer votre mot de passe actuel'; 
    }
    else {
        if (!password_verify($currpass, $t['cliPassword'])){
            $erreurs[] = 'Le mot de passe actuel ne correspond pas au mot de passe de votre compte.';
        }
        if(!empty($newpasse)){
            $nb = mb_strlen($newpasse, 'UTF-8');
            if ($nb < LMIN_PASSWORD || $nb > LMAX_PASSWORD){
                $erreurs[] = 'Le mot de passe doit être constitué de '. LMIN_PASSWORD . ' à ' . LMAX_PASSWORD . ' caractères.';
            }
        }
    }
    
    // vérification des noms et prenoms
    $nomprenom = trim($_POST['nomprenom']);
    
    if (empty($nomprenom)) {
        $erreurs[] = 'Le nom et le prénom doivent être renseignés.'; 
    }
    else {
        if (mb_strlen($nomprenom, 'UTF-8') > LMAX_NOMPRENOM){
            $erreurs[] = 'Le nom et le prénom ne peuvent pas dépasser ' . LMAX_NOMPRENOM . ' caractères.';
        }
        $noTags = strip_tags($nomprenom);
        if ($noTags != $nomprenom){
            $erreurs[] = 'Le nom et le prénom ne peuvent pas contenir de code HTML.';
        }
        else {
            mb_regex_encoding ('UTF-8'); //définition de l'encodage des caractères pour les expressions rationnelles multi-octets
            if( !mb_ereg_match('^[[:alpha:]]([\' -]?[[:alpha:]]+)*$', $nomprenom)){
                $erreurs[] = 'Le nom et le prénom contiennent des caractères non autorisés';
            }
        }
    }

    //Verif adresse
    $adresse = trim($_POST['adresse']);
    if(!empty($adresse)){
        if (mb_strlen($adresse, 'UTF-8') > LMAX_ADRESSE){
            $erreurs[] = 'L\'adresse ne peut pas dépasser ' . LMAX_ADRESSE . ' caractères.';
        }
        $noTags = strip_tags($adresse);
        if ($noTags != $adresse){
            $erreurs[] = 'L\'adresse ne peut pas contenir de code HTML.';
        }else {
            mb_regex_encoding ('UTF-8'); //définition de l'encodage des caractères pour les expressions rationnelles multi-octets
            if( !mb_ereg_match('[0-9]*[[:alpha:]]([\' -]?[[:alpha:]]+)*$', $adresse)){
                $erreurs[] = 'L\'adresse contient des caractères non autorisés';
            }
        }
    }

    //verif Ville
    $ville = trim($_POST['ville']);
    if(!empty($ville)){
        if (mb_strlen($ville, 'UTF-8') > LMAX_VILLE){
            $erreurs[] = 'La ville ne peut pas dépasser ' . LMAX_VILLE . ' caractères.';
        }
        $noTags = strip_tags($ville);
        if ($noTags != $ville){
            $erreurs[] = 'La ville ne peut pas contenir de code HTML.';
        }else {
            mb_regex_encoding ('UTF-8'); //définition de l'encodage des caractères pour les expressions rationnelles multi-octets
            if( !mb_ereg_match('^[[:alpha:]]([\' -]?[[:alpha:]]+)*$', $ville)){
                $erreurs[] = 'La ville contient des caractères non autorisés';
            }
        }
    }

    //Verif Code postal
    $codePostal = trim($_POST['codePostal']);
    if(!empty($codePostal)){
        $nb = mb_strlen($codePostal, 'UTF-8');
        if ($nb != L_CP){
            $erreurs[] = 'Le code postal doit être constitué de '. L_CP . ' chiffres.';
        }
        mb_regex_encoding ('UTF-8'); //définition de l'encodage des caractères pour les expressions rationnelles multi-octets
        if( !mb_ereg_match('^[0-9]*$', $codePostal)){
            $erreurs[] = 'Le code postal contient des caractères non autorisés';
        }
    }else{
        $erreurs[] = 'Le code postal doit être un nombre entier';
    }

    //Verif Pays
    $pays = trim($_POST['pays']);
    if(!empty($pays)){
        $nb = mb_strlen($pays, 'UTF-8');
        if ($nb > LMAX_PAYS){
            $erreurs[] = 'Le pays ne peut pas dépasser ' . LMAX_PAYS . ' caractères.';
        }
        $noTags = strip_tags($pays);
        if ($noTags != $pays){
            $erreurs[] = 'Le pays ne peut pas contenir de code HTML.';
        }else {
            mb_regex_encoding ('UTF-8');
            if( !mb_ereg_match('^[[:alpha:]]([\' -]?[[:alpha:]]+)*$', $pays)){
                $erreurs[] = 'Le pays contient des caractères non autorisés';
            }
        }
    }
    
    

    if (count($erreurs) == 0) {
        // vérification de l'unicité de l'adresse email 
        // (uniquement si pas d'autres erreurs, parce que ça coûte un bras)

        // pas utile, car l'adresse a déjà été vérifiée, mais tellement plus sécurisant...
        $email = at_bd_proteger_entree($bd, $email);
        $sql = "SELECT cliId FROM clients WHERE cliEmail = '$email' AND cliId != $id"; 
    
        $res = mysqli_query($bd,$sql) or at_bd_erreur($bd,$sql);
        
        if (mysqli_num_rows($res) != 0) {
            $erreurs[] = 'L\'adresse email spécifiée existe déjà.';
            // libération des ressources 
            mysqli_free_result($res);
            mysqli_close($bd);
        }
        else{
            // libération des ressources 
            mysqli_free_result($res);
        }
        
    }
    
    // s'il y a des erreurs ==> on retourne le tableau d'erreurs    
    if (count($erreurs) > 0) {  
        return $erreurs;    
    }
    
    // pas d'erreurs ==> enregistrement de l'utilisateur
    $nomprenom = at_bd_proteger_entree($bd, $nomprenom);

    if(empty($newpass)){
        $newpass = $currpass;
    }
    $newpass = password_hash($newpass,PASSWORD_DEFAULT);
    $newpass = at_bd_proteger_entree($bd,$newpass);

    $adresse = at_bd_proteger_entree($bd,$adresse);

    $ville = at_bd_proteger_entree($bd,$ville);

    $codePostal = at_bd_proteger_entree($bd,$codePostal);

    $pays = at_bd_proteger_entree($bd,$pays);


    
    

    
    $sql = "UPDATE clients
    SET cliEmail = '$email',
      cliPassword = '$newpass',
      cliNomPrenom = '$nomprenom',
      cliAdresse = '$adresse',
      cliCP = '$codePostal',
      cliVille = '$ville',
      cliPays = '$pays'
    WHERE cliId = $id
    ";
            
    mysqli_query($bd, $sql) or at_bd_erreur($bd, $sql);

    
    // libération des ressources
    mysqli_close($bd);
    
    // redirection vers la page protegee.php
    header('Location: compte.php'); //TODO : à modifier dans le projet
    exit();
}


?>