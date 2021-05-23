<?php

session_start();

require_once './bibli_generale.php';
require_once ('./bibli_bookshop.php');

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)

at_aff_debut('BookShop | Conditions', '../styles/bookshop.css', 'main');

at_aff_enseigne_entete();

atl_aff_contenu();

at_aff_pied('../');

at_aff_fin('main');

/** 
 * Permet l'affichage du contenu de la page
*/
function atl_aff_contenu(){
    echo '<h1>Conditions d\'utilisation</h1>',
    '<p>Les attaques XSS ça nous connaît, vous nous aurez pas comme ça.<span class=font6>(L\'expérience tout ça tout ça...)</span></p>';
}
?>