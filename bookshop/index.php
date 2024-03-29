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

ob_end_flush();


// ----------  Fonctions locales au script ----------- //

/** 
 *  Affichage du contenu de la page
 */
function atl_aff_contenu() {
    echo 
        '<h1>Bienvenue sur BookShop !</h1>',
        
        '<p>Passez la souris sur le logo et laissez-vous guider pour découvrir les dernières exclusivités de notre site. </p>',
        
        '<p>Nouveau venu sur BookShop ? Consultez notre <a href="./php/presentation.php">page de présentation</a> !</p>';

    //Dernière nouveautés
    $sql = "SELECT liID, liTitre, auNom, auPrenom, liPrix
    FROM livres INNER JOIN aut_livre ON al_IDLivre = liID 
                INNER JOIN auteurs ON al_IDAuteur = auID 
    ORDER BY liID DESC";

    //Top des ventes
    $sql2 = "SELECT ccIDLivre, liTitre, auNom, auPrenom, liPrix, SUM(ccQuantite) as somme
    FROM compo_commande INNER JOIN livres ON ccIDLIVRE = liID
                        INNER JOIN aut_livre ON al_IDLivre = liID 
                        INNER JOIN auteurs ON al_IDAuteur = auID
    GROUP BY ccIDLivre, auNOM, auPrenom
    ORDER BY somme DESC,ccIDLivre";

    $bd=at_bd_connecter();

    $res = mysqli_query($bd, $sql) or at_bd_erreur($bd,$sql);

    $all_books=array();
    $tLivres = array();
    $i=0;
    $lastID = -1;
    //Récupération des nouveautés
    while (($t = mysqli_fetch_assoc($res)) && $i<4) {
        if ($t['liID'] != $lastID) {
            if ($lastID != -1) {
                $tLivres[] = $livre;
                $all_books[] = $livre;
                $i++;
            }
            $lastID = $t['liID'];
            $livre = array( 'id' => $t['liID'], 
                'titre' => $t['liTitre'],
                'prix' => $t['liPrix'],
                'auteurs' => array(array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']))
            );
        }
        else {
            $livre['auteurs'][] = array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']);
        }       
    }
    // libération des ressources
    mysqli_free_result($res);
    atl_aff_section_livres(1,$tLivres);

    $tLivres=array();
    $lastID = -1;
    $i=0;

    $res = mysqli_query($bd, $sql2) or at_bd_erreur($bd,$sql2);
    //Récupération du top des ventes
    while (($t = mysqli_fetch_assoc($res)) && $i<4) {
        if ($t['ccIDLivre'] != $lastID) {
            if ($lastID != -1) {
                $tLivres[] = $livre;
                $size=count($all_books);
                $contains=false;
                for($j=0;$j<$size;++$j){
                    if($livre['id']===$all_books[$j]['id']){
                        $contains=true;
                    }
                }
                if(!$contains){
                    $all_books[] = $livre;
                }
                $i++;
            }
            $lastID = $t['ccIDLivre'];
            $livre = array( 'id' => $t['ccIDLivre'], 
                'titre' => $t['liTitre'],
                'prix' => $t['liPrix'],
                'auteurs' => array(array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']))
            );
        }
        else {
            $livre['auteurs'][] = array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']);
        }       
    }

    mysqli_free_result($res);
    atl_aff_section_livres(2,$tLivres);
    atl_get_action($all_books,$bd);
    
    mysqli_close($bd);
}

function atl_get_action($all_books,$bd){
    //Add to cart
    if(at_creation_panier() && isset($_GET['action']) && isset($_GET['id']) && $_GET['action']==="add" && at_est_entier($_GET['id'])){
        //Ici : récupération du prix dans lme tableau contenant tous les livres pour éviter les fraudes 
        //(impossible de placer prix dans la queryString, sinon il serait modifiable)
        $id=-1;
        $size=count($all_books);
        for($i=0;$i<$size;++$i){
            if($all_books[$i]['id']===$_GET['id']){
                $id=$i;
            }
        }
        if($id!==-1){
            at_button_ajouter_panier($_GET['id'],$all_books[$id]['prix'],'./php/');
        }else{
            header("Location: ".strtok($_SERVER['REQUEST_URI'],'?'));
        }
    }

    //Add to wish
    if(isset($_GET['action']) && isset($_GET['id'])  && $_GET['action']==="addW" && at_est_entier($_GET['id'])){
        at_ajouter_wishlist($bd,$_GET['id'],'./php/');
    }
}

/**
 *  Affichage d'une section de livres.
 *  Soit nouveautés, soit top des ventes.
 *
 *  @param  array       $tLivres      tableau de tableaux associatifs des infos sur un livre (id, auteurs(nom, prenom), titre, prix, pages, ISBN13, edWeb, edNom)
 *  @param  int         $nom          1 si affichage des nouveautés, 2 pour le top des ventes
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

    //Afichage des livres
    foreach ($tLivres as $livre) {
        echo 
            '<figure>',
                '<a class="addToCart" href="',$_SERVER['REQUEST_URI'],'?action=add&id=',$livre['id'],'" title="Ajouter au panier"></a>',
                '<a class="addToWishlist"  href="',$_SERVER['REQUEST_URI'],'?action=addW&id=',$livre['id'],'" title="Ajouter à la liste de cadeaux"></a>',
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

/*README
Pour ce qui est de l'ajout dans le panier ou dans la wishlist, nous passons certaines infos dans la querystring
via $_GET.
Rien de dangereux, seulement l'id du livre et l'action effectuée (les actions en question peuvent différer selon les pages)
Le prix du livre n'est donc pas modifiable par l'utilisateur etc...
La seul chose que ce dernier peut faire en manipulant la querystring c'est ajouter/supprimer/modifier la quantité manuellement
en saisissant les couples correspondant dans la querystring sur une page donnée. Cela ne réprensente donc aucunement un danger,
toutes les vérifications concernant les valeurs étant faites.
*/

?>