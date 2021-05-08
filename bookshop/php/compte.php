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

// si utilisateur n'est pas authentifié, on le redirige vers la page login.php
if (!at_est_authentifie()){
    header("Location: ./login.php");
    exit();
}

/*------------------------- Etape 2 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

at_aff_debut('BookShop | Inscription', '../styles/bookshop.css', 'main');

at_aff_enseigne_entete();

atl_aff_contenu();

at_aff_pied('../');

at_aff_fin('main');

ob_end_flush();


// ----------  Fonctions locales du script ----------- //

function atl_aff_contenu(){
    $bd = at_bd_connecter();

    $sql = "SELECT cliEmail,cliNomPrenom,cliPassword,cliAdresse,cliVille,cliCP,cliPays FROM clients WHERE cliID=".$_SESSION['id'];
    $res = mysqli_query($bd, $sql) or at_bd_erreur($bd, $sql);
    $t = mysqli_fetch_assoc($res);
    echo '<h1>Compte Utilisateur</h1>';
    echo '<form method="post" action="compte.php">',
        '<table>';
        atl_afficher_ligne_modifiable("Email",$t['cliEmail'],'email');
        atl_afficher_ligne_modifiable("Nom et Prénom",$t['cliNomPrenom'],'text');
        atl_afficher_ligne_modifiable("Mot de passe",$t['cliPassword'],'password');
        atl_afficher_ligne_modifiable("Adresse",$t['cliAdresse'],'text');
        atl_afficher_ligne_modifiable("Ville",$t['cliVille'],'text');
        atl_afficher_ligne_modifiable("Code Postal",$t['cliCP'],'text');
        atl_afficher_ligne_modifiable("Pays",$t['cliPays'],'text');
    echo '<tr>',
    '<td colspan="2"><input type="submit" name="modif" value"btnValider"></td>',
    '</tr>',
    '</table>',
    '</form>';
}

function atl_aff_ligne_bouton($nom,$valeur){
    echo "<tr>",
    "<td>$nom :</td>",
    "<td>$valeur</td>",
    '<td><input type="submit" name="btn'.str_replace(array(" ","et","é"),array("","","e"),$nom).'" value="Modifier"></td>';
}


function atl_afficher_ligne_modifiable($nom,$valeur,$prefix_id){
    echo "<tr>",
    "<td>$nom :</td>",
    "<td>$valeur</td>",
    '<td><input id="'.$prefix_id.'" name="'.str_replace(" ","",$nom).'";</td>';
}


?>