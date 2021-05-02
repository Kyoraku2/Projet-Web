<?php

ob_start(); //démarre la bufferisation
session_start();

require_once '../php/bibli_generale.php';
require_once ('../php/bibli_bookshop.php');

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)

at_aff_debut('BookShop | Détail', '../styles/bookshop.css', 'main');

at_aff_enseigne_entete();

if(at_creation_panier()){
    print_r($_SESSION['panier']);
}

at_aff_pied();

at_aff_fin('main');

ob_end_flush();

?>