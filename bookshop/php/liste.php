<?php

ob_start(); //démarre la bufferisation
session_start();

require_once '../php/bibli_generale.php';
require_once '../php/bibli_bookshop.php';

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)

// erreurs détectées dans l'URL
$erreurs = array();

$recherche = array('quoi' => '');

if ($_GET){ // s'il y a des paramètres dans l'URL
    if (! at_parametres_controle('get', array(),array('quoi','action','id'))){
        $erreurs[] = 'L\'URL doit être de la forme "liste.php ou liste.php?quoi=mail".';
    }else{
        if(!isset($_GET['action'])){
            $recherche['quoi'] = trim($_GET['quoi']);
            $l1 = mb_strlen($recherche['quoi'], 'UTF-8');
            if ($l1 != mb_strlen(strip_tags($recherche['quoi']), 'UTF-8')){
                $erreurs[] = 'Le critère de recherche ne doit pas contenir de tags HTML.';
            }
            if (empty($recherche['quoi'])){
                $erreurs[] = 'L\'adresse mail ne doit pas être vide.'; 
            }else {
                if (mb_strlen($recherche['quoi'], 'UTF-8') > LMAX_EMAIL){
                    $erreurs[] = 'L\'adresse mail ne peut pas dépasser '.LMAX_EMAIL.' caractères.';
                }
                // la validation faite par le navigateur en utilisant le type email pour l'élément HTML input
                // est moins forte que celle faite ci-dessous avec la fonction filter_var()
                // Exemple : 'l@i' passe la validation faite par le navigateur et ne passe pas
                // celle faite ci-dessous
                if(! filter_var($recherche['quoi'], FILTER_VALIDATE_EMAIL)) {
                    $erreurs[] = 'L\'adresse mail n\'est pas valide.';
                }
            }
        }
    }
}


at_aff_debut('BookShop | Liste', '../styles/bookshop.css', 'main');

at_aff_enseigne_entete();

atl_aff_contenu($recherche,$erreurs);

at_aff_pied();

at_aff_fin('main');

// fin du script --> envoi de la page 
ob_end_flush();

function atl_aff_contenu($recherche,$erreurs){
    if(!at_est_authentifie()){
        echo '<h3>Vous devez vous identidier pour pouvoir accéder à votre liste de souhait(s)</h3>';
        return;
    }
    $id=0;
    if(!isset($_SESSION['id'])){
        echo '<p>Erreur, veuillez contacter l\'administrateur du site.</p>';
        return;
    }
    // ouverture de la connexion, requête
    $bd = at_bd_connecter();

    echo '<h3>Recherche d\'une liste de souhait(s) par une adresse mail complète.</h3>'; 
    echo '<form action="liste.php" method="get">',
            '<p>Rechercher <input type="text" name="mail" minlength="5" value="', at_html_proteger_sortie($recherche['quoi']),'" required>', 
                '<input type="submit" value="Rechercher">', // pas d'attribut name pour qu'il n'y ait pas d'élément correspondant au bouton submit dans l'URL
                                                            // lors de la soumission du formulaire
            '</p>', 
          '</form>';
    if ($erreurs) {
        $nbErr = count($erreurs);
        $pluriel = $nbErr > 1 ? 's':'';
        echo '<p class="error">',
            '<strong>Erreur',$pluriel, ' détectée', $pluriel, ' :</strong>';
        for ($i = 0; $i < $nbErr; $i++) {
                echo '<br>', $erreurs[$i];
        }
        echo '</p>';
        return; // ===> Fin de la fonction
    }
    if(!isset($_GET['quoi'])){
        echo '<h3>Voici votre liste de souhait(s)</h3>';
        $id=at_bd_proteger_entree($bd,$_SESSION['id']);
        $sql="SELECT cliID,liID, liTitre, liPrix, liPages, liISBN13, edNom, edWeb, auNom, auPrenom 
        FROM livres,clients,listes,auteurs,aut_livre,editeurs
        WHERE liID=al_IDLivre
        AND al_IDAuteur=auID
        AND liIDEditeur=edID
        AND liID=listIDLivre
        AND cliID=listIDClient
        AND cliID=$id";

        $res = mysqli_query($bd, $sql) or at_bd_erreur($bd,$sql);
        $livres=array();
        $lastID = -1;
        while ($t = mysqli_fetch_assoc($res)) {
            if ($t['liID'] != $lastID) {
                if ($lastID != -1) {
                    atl_aff_livre($livre); 
                    $livres[] = $livre;
                }
                $lastID = $t['liID'];
                $livre = array( 'id' => $t['liID'], 
                'titre' => $t['liTitre'],
                'edNom' => $t['edNom'],
                'edWeb' => $t['edWeb'],
                'pages' => $t['liPages'],
                'ISBN13' => $t['liISBN13'],
                'prix' => $t['liPrix'],
                'auteurs' => array(array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']))
                );
            }else{
                $livre['auteurs'][] = array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']);
            }
        }
        // libération des ressources
        if ($lastID != -1) {
            atl_aff_livre($livre);
            $livres[] = $livre;
            mysqli_free_result($res);
        }else{
            mysqli_free_result($res);
            mysqli_close($bd);
        }

        mysqli_close($bd);

        //Add to crate
        if(at_creation_panier() && isset($_GET['action']) && isset($_GET['id']) && $_GET['action']==="add" && at_est_entier($_GET['id'])){
            //récupération du prix pour éviter les fraudes (impossible de placer prix dans la queryString)
            $id=-1;
            $size=count($livres);
            for($i=0;$i<$size;++$i){
                if($livres[$i]['id']===$_GET['id']){
                    $id=$i;
                }
            }
            if($id!==-1){
                at_ajouter_article($_GET['id'],1,$livres[$id]['prix']);
                unset($_GET['action']);
                $url=strtok($_SERVER["REQUEST_URI"], '?');
                header("Location: $url");
            }
        }
    }
}

/**
 *  Affichage d'un livre.
 *
 *  @param  array       $livre      tableau associatif des infos sur un livre (id, auteurs(nom, prenom), titre, prix, pages, ISBN13, edWeb, edNom)
 *
 */
function atl_aff_livre($livre) {
    // Le nom de l'auteur doit être encodé avec urlencode() avant d'être placé dans une URL, sans être passé auparavant par htmlentities()
    $auteurs = $livre['auteurs'];
    $livre = at_html_proteger_sortie($livre);
    echo ' id ',$livre['id'];
    echo 
        '<article class="arRecherche">', 
            // TODO : à modifier pour le projet  
            '<a class="addToCart" href="',$_SERVER['REQUEST_URI'],'?action=add&id=',$livre['id'],'" title="Ajouter au panier"></a>',
            //'<a class="addToWishlist" href="#" title="Ajouter à la liste de cadeaux"></a>',
            '<a href="details.php?article=', $livre['id'], '" title="Voir détails"><img src="../images/livres/', $livre['id'], '_mini.jpg" alt="', 
            $livre['titre'],'"></a>',
            '<h5>', $livre['titre'], '</h5>',
            'Ecrit par : ';
    $i = 0;
    foreach ($auteurs as $auteur) {
        echo $i > 0 ? ', ' : '', '<a href="recherche.php?type=auteur&amp;quoi=', urlencode($auteur['nom']), '">',
        at_html_proteger_sortie($auteur['prenom']), ' ', at_html_proteger_sortie($auteur['nom']) ,'</a>';
        $i++;
    }
            
    echo    '<br>Editeur : <a class="lienExterne" href="http://', trim($livre['edWeb']), '" target="_blank">', $livre['edNom'], '</a><br>',
            'Prix : ', $livre['prix'], ' &euro;<br>',
            'Pages : ', $livre['pages'], '<br>',
            'ISBN13 : ', $livre['ISBN13'], 
        '</article>';
}

//Vérifier paramètre
//Manque le résultat de la recherche
?>