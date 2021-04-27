<?php

ob_start(); //démarre la bufferisation
session_start();

require_once './php/bibli_generale.php';
require_once ('./php/bibli_bookshop.php');

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)

at_aff_debut('BookShop | Bienvenue', './styles/bookshop.css', 'main');

at_aff_enseigne_entete('./');

atl_aff_contenu();

at_aff_pied();

at_aff_fin('main');



// ----------  Fonctions locales au script ----------- //

/** 
 *  Affichage du contenu de la page
 */
function atl_aff_contenu() {
    
    echo 
        '<h1>Bienvenue sur BookShop !</h1>',
        
        '<p>Passez la souris sur le logo et laissez-vous guider pour découvrir les dernières exclusivités de notre site. </p>',
        
        '<p>Nouveau venu sur BookShop ? Consultez notre <a href="./php/presentation.php">page de présentation</a> !</p>';
    
        
    $derniersAjouts = array(
        array(  'id'      => 42, 
                'auteurs' => array( array('prenom' => 'George', 'nom' => 'Orwell')), 
                'titre'   => '1984'),
        array(  'id'      => 41, 
                'auteurs' => array( array('prenom' => 'Robert', 'nom' => 'Kirkman'),
                                    array('prenom' => 'Charlie', 'nom' => 'Adlard')), 
                'titre'   => 'The Walking Dead - T16 Un vaste monde'),
        array(  'id'      => 40, 
                'auteurs' => array( array('prenom' => 'Ray', 'nom' => 'Bradbury')), 
                'titre'   => 'L\'homme illustré'),   
        array(  'id'      => 39, 
                'auteurs' => array( array('prenom' => 'Alan', 'nom' => 'Moore'),
                                    array('prenom' => 'David', 'nom' => 'Lloyd')), 
                'titre'   => 'V pour Vendetta'),  
              ); 

    atl_aff_section_livres(1, $derniersAjouts);
    
    
    $meilleursVentes = array(
        array(  'id'      => 20, 
                'auteurs' => array( array('prenom' => 'Alan', 'nom' => 'Moore'),
                                    array('prenom' => 'Dave', 'nom' => 'Gibbons')),
                'titre'   => 'Watchmen'),
        array(  'id'      => 39, 
                'auteurs' => array( array('prenom' => 'Alan', 'nom' => 'Moore'),
                                    array('prenom' => 'David', 'nom' => 'Lloyd')), 
                'titre'   => 'V pour Vendetta'), 
        array(  'id'      => 27, 
                'auteurs' => array( array('prenom' => 'Robert', 'nom' => 'Kirkman'),
                                    array('prenom' => 'Jay', 'nom' => 'Bonansinga')), 
                'titre'   => 'The Walking Dead - La route de Woodbury'),
        array(  'id'      => 34, 
                'auteurs' => array( array('prenom' => 'Aldous', 'nom' => 'Huxley')), 
                'titre'   => 'Le meilleur des mondes'),   
         
              ); 
    
    atl_aff_section_livres(2, $meilleursVentes);    
}


/** 
 *  Affichage d'une section de livres
 *
 *  @param  integer $num        numéro de la section (1 pour les dernières nouveautés, 2 pour les meilleures ventes) 
 *  @param  array   $tLivres    tableau contenant un élément (tableau associatif) pour chaque livre (id, auteurs(nom, prenom), titre)
 *
 */
function atl_aff_section_livres($num, $tLivres) {
    echo '<section>';
    if ($num == 1){
        echo  '<h2>Dernières nouveautés </h2>',
              '<p>Voici les 4 derniers articles ajoutés dans notre boutique en ligne :</p>';   
    }
    elseif ($num == 2){
        echo  '<h2>Top des ventes</h2>',
              '<p>Voici les 4 articles les plus vendus :</p>';
    }

    foreach ($tLivres as $livre) {
        echo 
            '<figure>',
                // TODO : à modifier pour le projet  
                '<a class="addToCart" href="#" title="Ajouter au panier"></a>',
                '<a class="addToWishlist" href="#" title="Ajouter à la liste de cadeaux"></a>',
                '<a href="php/details.php?article=', $livre['id'], '" title="Voir détails"><img src="./images/livres/', 
                $livre['id'], '_mini.jpg" alt="', $livre['titre'],'"></a>',
                '<figcaption>';
        $auteurs = $livre['auteurs']; 
        $i = 0;
        foreach ($livre['auteurs'] as $auteur) {  
            if ($i > 0) {
                echo ', ';
            }
            ++$i;
            echo    '<a title="Rechercher l\'auteur" href="php/recherche.php?type=auteur&amp;quoi=', urlencode($auteur['nom']), '">', 
                    mb_substr($auteur['prenom'], 0, 1, 'UTF-8'), '. ', $auteur['nom'], '</a>';
        }
        echo        '<br>', 
                    '<strong>', $livre['titre'], '</strong>',
                '</figcaption>',
            '</figure>';
    }
    echo '</section>';
}
    
?>
