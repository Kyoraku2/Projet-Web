<?php

ob_start(); //démarre la bufferisation
session_start();

require_once './bibli_generale.php';
require_once ('./bibli_bookshop.php');

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)

at_aff_debut('BookShop | A propos', '../styles/bookshop.css', 'main');

at_aff_enseigne_entete();

atl_aff_contenu();

at_aff_pied('../');

at_aff_fin('main');

ob_end_flush();

/** 
 * Permet l'affichage du contenu de la page
*/
function atl_aff_contenu(){
    echo '<h1>A propos de bookshop</h1>',
    '<h2>Notre entreprise</h2>',
    '<p>Tout ce qu\'il y a plus haut ça veut rien dire. mais vous en faites pas c\'est sympa par ici.<span class=font6>(vraiment en plus c\'est pas un mensonge)</span></p>';
}

?>