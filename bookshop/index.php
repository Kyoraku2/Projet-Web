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

    $sql = "SELECT liID, liTitre, auNom, auPrenom, liPrix
    FROM livres INNER JOIN aut_livre ON al_IDLivre = liID 
                INNER JOIN auteurs ON al_IDAuteur = auID 
    ORDER BY liID DESC LIMIT 8";

    $sql2 = "SELECT ccIDLivre, liTitre, auNom, auPrenom, liPrix
    FROM compo_commande INNER JOIN livres ON ccIDLIVRE = liID
                        INNER JOIN aut_livre ON al_IDLivre = liID 
                        INNER JOIN auteurs ON al_IDAuteur = auID
    GROUP BY ccIDLivre, auNOM, auPrenom
    ORDER BY SUM(ccQuantite) DESC,ccIDLivre
    LIMIT 8";

    $bd=at_bd_connecter();

    $res = mysqli_query($bd, $sql) or at_bd_erreur($bd,$sql);

    $all_books=array();
    $tLivres = array();
    $i=0;
    $lastID = -1;
    
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
    //Add to crate
    if(at_creation_panier() && isset($_GET['action']) && isset($_GET['id']) && $_GET['action']==="add" && at_est_entier($_GET['id'])){
        //récupération du prix pour éviter les fraudes (impossible de placer prix dans la queryString)
        $id=-1;
        $size=count($all_books);
        for($i=0;$i<$size;++$i){
            if($all_books[$i]['id']===$_GET['id']){
                $id=$i;
            }
        }
        if($id!==-1){
            at_ajouter_article($_GET['id'],1,$all_books[$id]['prix']);
            unset($_GET['action']);
            if(isset($_SERVER['HTTP_REFERER'])){
                header("Location: ".$_SERVER['HTTP_REFERER']);
            }else{
                header("Location: ".strtok($_SERVER['REQUEST_URI'],'?'));
            }
        }else{
            header("Location: ".strtok($_SERVER['REQUEST_URI'],'?'));
        }
    }

    //Add to wish
    if(isset($_GET['action']) && isset($_GET['id'])  && $_GET['action']==="addW" && at_est_entier($_GET['id'])){
        if(!at_est_authentifie()){
            unset($_GET['action']);
            header("Location: ./php/login.php");
            return;
        }
        //Check for duplicate or non existant
        $id_livre=at_bd_proteger_entree($bd,$_GET['id']);
        $id_client=at_bd_proteger_entree($bd,$_SESSION['id']);
        $sql="SELECT liID
        FROM livres
        WHERE liID=$id_livre";
        $res = mysqli_query($bd, $sql) or at_bd_erreur($bd,$sql);   
        $leave=(mysqli_num_rows($res)==0)?1:0;
        mysqli_free_result($res);

        if($leave===0){
            $sql="SELECT listIDClient,listIDLivre
            FROM listes
            WHERE listIDClient=$id_client
            AND listIDLivre=$id_livre";
            $res = mysqli_query($bd, $sql) or at_bd_erreur($bd,$sql);
            $insert=(mysqli_num_rows($res)==0)?1:0;
            mysqli_free_result($res);
            //Insert
            if($insert===1){
                $sql =  "INSERT listes (listIDLivre,listIDClient)
                VALUES ($id_livre,$id_client)";
                $res = mysqli_query($bd, $sql) or at_bd_erreur($bd,$sql);
            }
            unset($_GET['action']);
            unset($_GET['id']);
            header("Location: ".$_SERVER['HTTP_REFERER']);
        }
        header("Location: ".strtok($_SERVER['REQUEST_URI'],'?'));
    }
}

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

//TODO :

//Requete SQL insertion panier
//Pagination
//Historique commande
//Requete SQL modification utilisateur
//check parametre + autres verif si besoin
//Vérification QueryString partout (avec les fonctions de merlet)
//+gestion erreurs un peu sur toutes les pages
//Check longueur max de champs de la BD
//check les étapes de bd sur toutes les pages/toutes les requêtes: ouverture, recupération, libération, fermeture 
?>