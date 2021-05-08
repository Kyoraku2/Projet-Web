<?php

session_start();

require_once './bibli_generale.php';
require_once ('./bibli_bookshop.php');

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)

at_aff_debut('BookShop | Confident', '../styles/bookshop.css', 'main');

at_aff_enseigne_entete();

atl_aff_contenu();

at_aff_pied('../');

at_aff_fin('main');


function atl_aff_contenu(){
    echo '<h1>Politique de confidentialité</h1>',
    '<p>Normalement tout est safe.</p>';
}
?>