<?php
/* ------------------------------------------------------------------------------
    Architecture de la page
    - étape 1 : vérifications diverses
    - étape 2 : traitement et génération du code HTML de la page
------------------------------------------------------------------------------*/

ob_start(); //démarre la bufferisation
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_bookshop.php';

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)

/*------------------------- Etape 1 --------------------------------------------
- vérifications diverses
------------------------------------------------------------------------------*/
if (!at_est_authentifie()){
    $page = isset($_POST['destination']) ? $_POST['destination'] : '../index.php';
    header("Location: $page");
    exit();
}
/*------------------------- Etape 2 --------------------------------------------
- traitement et génération du code HTML de la page
------------------------------------------------------------------------------*/
/**
 * Connexion à la base de données et requête sql pour récupérer tous les informations sur tous les clients
 * On cherche ensuite la ligne qui correspond à l'utilisateur actuel
 */
$bd = at_bd_connecter();
    
    $sql = "SELECT cliID,cliEmail,cliNomPrenom,cliPassword,cliAdresse,cliVille,cliCP,cliPays FROM clients";
    $res = mysqli_query($bd, $sql) or at_bd_erreur($bd, $sql);
    $t = mysqli_fetch_assoc($res);
    while($t['cliID']!=$_SESSION['id']){
        $t = mysqli_fetch_assoc($res);
    }
    
    

at_aff_debut('BookShop | Compte', '../styles/bookshop.css', 'main');

at_aff_enseigne_entete();

$canModify = isset($_POST['modif']) ? TRUE : FALSE;
$err = (isset($_POST['currpass'])) ? atl_traitement_modification($t,$bd,$res) : array();
atl_aff_contenu($canModify,$t,$err);



at_aff_pied('../');

at_aff_fin('main');

ob_end_flush();


// ----------  Fonctions locales du script ----------- //

/**
 * Fonction permettant l'affichage du formulaire permettant à l'utilisateur de modifier ses informations personnelles ou des informations de l'utilisateur non modifiable
 * @param   boolean $canModify  booléen indiquant si l'on doit afficher le formulaire pour modifier les informations ou si l'on doit juste afficher les informations de l'utilisateur
 * @param   array   $t          tableau correspondant à la ligne de la table clients contenant les informations de l'utilisateur connecté
 * @param   array   $err        tableau contenant les erreurs faites par l'utilisateur lors de la validation du formulaire, on affiche le tableau si et seulement si celui-ci contient des erreurs 
 */
function atl_aff_contenu($canModify,$t,$err){
    //Récupération des informations de l'utilisateur et protection des chaines
    $email = at_html_proteger_sortie(trim($t['cliEmail']));
    $nomprenom = at_html_proteger_sortie(trim($t['cliNomPrenom']));
    $adresse = at_html_proteger_sortie(trim($t['cliAdresse']));
    $ville = at_html_proteger_sortie(trim($t['cliVille']));
    $codePostal = at_html_proteger_sortie(trim($t['cliCP']));
    $pays = at_html_proteger_sortie(trim($t['cliPays']));

    if($canModify === FALSE){
        echo '<h1>Compte Utilisateur</h1>',
        '<p>Pour accéder à votre historique de commande(s), cliquez <a href="./command.php" title="Historique commandes">ici</a>.</p>';
        echo '<form method="post" action="compte.php">',
            '<table>';
            atl_aff_ligne("Email",$email);
            atl_aff_ligne("Nom et Prénom",$nomprenom);
            atl_aff_ligne("Mot de passe","");
            atl_aff_ligne("Adresse",$adresse);
            atl_aff_ligne("Ville",$ville);
            atl_aff_ligne("Code Postal",$codePostal);
            atl_aff_ligne("Pays",$pays);
        echo '<tr>',
        '<td colspan="2"><input type="submit" name="modif" value="Modifier les informations" style="width:200px;background-size: 200px 26px"></td>',
        '</tr>',
        '</table>',
        '</form>';
    }else{
        
        echo '<h1>Compte Utilisateur</h1>';

        if (count($err) > 0) {
            echo '<p class="error">Votre inscription n\'a pas pu être réalisée à cause des erreurs suivantes : ';
            foreach ($err as $v) {
                echo '<br> - ', $v;
            }
            echo '</p>';    
        }

        //affichage du formulaire
        echo '<form method="post" action="compte.php">',
            '<table>';
        at_aff_ligne_input('Email :', array('type' => 'email', 'name' => 'email', 'value' => $email, 'required' => false));
        at_aff_ligne_input('Nouveau mot de passe :', array('type' => 'password', 'name' => 'newpass', 'value' => ''));
        at_aff_ligne_input('Nom et prénom :', array('type' => 'text', 'name' => 'nomprenom', 'value' => $nomprenom, 'required' => false));
        at_aff_ligne_input('Adresse :', array('type' => 'text', 'name' => 'adresse', 'value' => $adresse));
        at_aff_ligne_input('Ville :', array('type' => 'text', 'name' => 'ville', 'value' => $ville));
        at_aff_ligne_input('Code Postal :', array('type' => 'number', 'name' => 'codePostal', 'value' => $codePostal));
        at_aff_ligne_input('Pays :', array('type' => 'text', 'name' => 'pays', 'value' => $pays));
        at_aff_ligne_input('Mot de passe actuel :', array('type' => 'password', 'name' => 'currpass', 'value' => '', 'required' => false));        
        echo '<tr>',
        '<td colspan="2">
        <input type="submit" name="modif" value="Valider">',
        '<input type="reset" value="Réinitialiser">',
        '</td>',
        
        '</tr>',
        '</table>',
        '</form>';
    }
}

/**
 * Fonction permettant l'affichage d'une ligne dans la fonction atl_aff_contenu
 * @param   string  $nom    nom de la case du formulaire
 * @param   string  $valeur valeur de la case
*/
function atl_aff_ligne($nom,$valeur){
    //On affiche "Mot de passe caché" à la place d'afficher le mot de passe du client hashé
    if($nom === "Mot de passe"){
        $valeur = "<strong>Mot de passe caché</strong>";
    }
    echo "<tr>",
    "<td>$nom :</td>",
    "<td>$valeur</td>";
}

/**
 * Fonction permettant le traitement de la modification des informations par l'utilisateur 
 * @param   array           $t      tableau correspondant à la ligne de la table clients contenant les informations de l'utilisateur connecté
 * @param   object          $bd     connecteur à la base de données
 * @param   mysqli_result   $res    resultat de la requête sql (informations de tous les clients)  
 * @return  array|void              le tableau d'erreurs s'il y en a sinon renvoie vers la page compte.php avec l'affichage des informations de l'utilisateur
 */
function atl_traitement_modification($t,$bd,$res) {

    //Pour vérifier les tentatives de piratage
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
    $nb = mb_strlen($currpass, 'UTF-8');
    if ($nb == 0){
        $erreurs[] = 'Vous devez rentrer votre mot de passe actuel'; 
    }else{
        if (!password_verify($currpass, $t['cliPassword'])){
            $erreurs[] = 'Le mot de passe actuel rentré ne correspond pas au mot de passe de votre compte.';
        }

        $nb = mb_strlen($newpass, 'UTF-8');
        if ($nb != 0 && $nb < LMIN_PASSWORD || $nb > LMAX_PASSWORD){
            $erreurs[] = 'Le nouveau mot de passe doit être constitué de '. LMIN_PASSWORD . ' à ' . LMAX_PASSWORD . ' caractères.';
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
        if($codePostal !== '0'){
            $erreurs[] = 'Le code postal doit être un nombre entier';
        }
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

        // pas utile, car l'adresse a déjà été vérifiée, mais tellement plus sécurisant...
        $email = at_bd_proteger_entree($bd, $email);
        mysqli_data_seek($res,0);

        while($t = mysqli_fetch_assoc($res)){
            if($t['cliID'] != $_SESSION['id'] && $t['cliEmail'] == $email){
                $erreurs[] = 'L\'adresse email spécifiée existe déjà.';
            }
        }
    }
        
    
    // s'il y a des erreurs ==> on libère les ressources et on retourne le tableau d'erreurs    
    if (count($erreurs) > 0) { 
        mysqli_free_result($res); 
        return $erreurs;    
    }
    
    // pas d'erreurs ==> protection des données et enregistrement des nouvelles informations
    $nomprenom = at_bd_proteger_entree($bd, $nomprenom);

    //Si le nouveau mot de passe est vide alors on garde le même mot de passe
    //On n'utilise pas la fonction empty() car si l'utilisateur rentre comme mot de passe "false" la fonction empty() retournera true
    $nb = mb_strlen($newpass, 'UTF-8'); 
    if($nb == 0){
        $newpass = $currpass;
    }
    $newpass = password_hash($newpass,PASSWORD_DEFAULT);
    $newpass = at_bd_proteger_entree($bd,$newpass);

    $adresse = at_bd_proteger_entree($bd,$adresse);

    $ville = at_bd_proteger_entree($bd,$ville);

    $codePostal = at_bd_proteger_entree($bd,$codePostal);

    $pays = at_bd_proteger_entree($bd,$pays);



    
    
    //requête permettant de mettre à jour la base de données avec les nouvelles informations
    
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
    
    // redirection vers la page compte.php pour l'affichage des nouvelles informations de l'utilisateur 
    header('Location: compte.php'); 
    exit();
}

?>