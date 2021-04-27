<?php
ob_start(); //démarre la bufferisation


require_once 'bibli_generale.php';
require_once 'bibli_bookshop.php';

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)


em_aff_debut('BookShop | Inscription');

echo '<h1>Réception du formulaire<br>Inscription utilisateur</h1>';

if( !em_parametres_controle('post', array('email', 'nomprenom', 'naissance_j', 'naissance_m', 'naissance_a', 
                                            'passe1', 'passe2', 'btnSInscrire'))) {
    header('Location: ../index.php');
    exit();
}

$erreurs = array();

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
$passe1 = trim($_POST['passe1']);
$passe2 = trim($_POST['passe2']);
if ($passe1 !== $passe2) {
    $erreurs[] = 'Les mots de passe doivent être identiques.';
}
$nb = mb_strlen($passe1, 'UTF-8');
if ($nb < LMIN_PASSWORD || $nb > LMAX_PASSWORD){
    $erreurs[] = 'Le mot de passe doit être constitué de '. LMIN_PASSWORD . ' à ' . LMAX_PASSWORD . ' caractères.';
}

// vérification de la date de naissance
if (! (em_est_entier($_POST['naissance_j']) && em_est_entre($_POST['naissance_j'], 1, 31))){
    header('Location: ../index.php');
    exit(); 
}

if (! (em_est_entier($_POST['naissance_m']) && em_est_entre($_POST['naissance_m'], 1, 12))){
    header('Location: ../index.php');
    exit(); 
}
$anneeCourante = (int) date('Y');
if (! (em_est_entier($_POST['naissance_a']) && em_est_entre($_POST['naissance_a'], $anneeCourante  - NB_ANNEE_DATE_NAISSANCE + 1, $anneeCourante))){
    header('Location: ../index.php');
    exit();  
}

$jour = (int)$_POST['naissance_j'];
$mois = (int)$_POST['naissance_m'];
$annee = (int)$_POST['naissance_a'];
if (!checkdate($mois, $jour, $annee)) {
    $erreurs[] = 'La date de naissance n\'est pas valide.';
}
else if (mktime(0,0,0,$mois,$jour,$annee+18) > time()) {
    $erreurs[] = 'Vous devez avoir au moins 18 ans pour vous inscrire.'; 
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


if (count($erreurs) == 0) {
    // vérification de l'unicité de l'adresse email 
    // (uniquement si pas d'autres erreurs, parce que ça coûte un bras)
    $bd = em_bd_connecter();

    // pas utile, car l'adresse a déjà été vérifiée, mais tellement plus sécurisant...
    $email = em_bd_proteger_entree($bd, $email);
    $sql = "SELECT cliID FROM clients WHERE cliEmail = '$email'"; 

    $res = mysqli_query($bd,$sql) or em_bd_erreur($bd,$sql);
    
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
    echo '<p>Votre inscription n\'a pas pu être réalisée à cause des erreurs suivantes : ';
    foreach ($erreurs as $v) {
        echo '<br> - ', $v;
    }
    echo '</p>';
    em_aff_fin();
    ob_end_flush();
    exit(); //==> FIN DU SCRIPT
}


$nomprenom = em_bd_proteger_entree($bd, $nomprenom);

$passe1 = password_hash($passe1, PASSWORD_DEFAULT);
$passe1 = em_bd_proteger_entree($bd, $passe1);

$aaaammjj = $annee*10000  + $mois*100 + $jour;


$sql = "INSERT INTO clients(cliNomPrenom, cliEmail, cliDateNaissance, cliPassword, cliAdresse, cliCP, cliVille, cliPays) 
        VALUES ('$nomprenom', '$email', $aaaammjj, '$passe1', '', 0, '', '')";
        
mysqli_query($bd, $sql) or em_bd_erreur($bd, $sql);

// libération des ressources
mysqli_close($bd);

echo '<p>Un nouvel utilisateur a bien été enregistré</p>';

em_aff_fin();

?>
