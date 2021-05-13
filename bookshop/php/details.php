<?php

ob_start(); //démarre la bufferisation
session_start();
require_once '../php/bibli_generale.php';
require_once ('../php/bibli_bookshop.php');

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)

at_aff_debut('BookShop | Détail', '../styles/bookshop.css', 'main');

at_aff_enseigne_entete();

$erreurs = array();
$id=-1;

if($_GET){
    if (! at_parametres_controle('get', array('article'),array('action'))){
        $erreurs[] = ' - L\'URL doit être de la forme "detail.php?article=id".';
    }else{
        if(is_numeric($_GET['article'])===false){
            $erreurs[] = ' - La valeur de article doit être numérique.';
        }else{
            $id=$_GET['article'];
        }
    }
}else{
    $erreurs[] = ' - L\'URL doit être de la forme "recherche.php?type=auteur&quoi=Moore".';
}

atl_aff_contenu($id,$erreurs);

at_aff_pied('../');

at_aff_fin('main');

ob_end_flush();

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
    echo '<article class="arRecherche">', 
    // TODO : à modifier pour le projet  
    '<a class="addToCart" href="',$_SERVER['REQUEST_URI'],'&amp;action=add" title="Ajouter au panier"></a>',
    '<a class="addToWishlist"  href="',$_SERVER['REQUEST_URI'],'&amp;action=addW" title="Ajouter à la liste de cadeaux"></a>',
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
            'ISBN13 : ', $livre['ISBN13'], '<br>',
            '</article>','<br>',
            '<p>Resume : <em>',(!empty($livre['resume']))?$livre['resume']:"Résumé à venir",'</em></p>';
}

function atl_aff_contenu($id,$erreurs){
    $bd = at_bd_connecter();
    $sql =  "SELECT liID, liTitre, liPrix, liPages, liISBN13, liResume, edNom, edWeb, auNom, auPrenom 
    FROM livres,editeurs,aut_livre,auteurs
    WHERE liID = $id
    AND al_IDAuteur = auID
    AND al_IDLivre = liID 
    AND liIDEditeur = edID";
    $res = mysqli_query($bd, $sql) or at_bd_erreur($bd,$sql);
    
    $lastID = -1;
    while ($t = mysqli_fetch_assoc($res)) {
        if ($t['liID'] != $lastID) {
            if ($lastID != -1) {
                atl_aff_livre($livre);
            }
            $lastID = $t['liID'];
            $livre = array( 'id' => $t['liID'], 
                            'titre' => $t['liTitre'],
                            'edNom' => $t['edNom'],
                            'edWeb' => $t['edWeb'],
                            'pages' => $t['liPages'],
                            'ISBN13' => $t['liISBN13'],
                            'prix' => $t['liPrix'],
                            'resume' => $t['liResume'],
                            'auteurs' => array(array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']))
                        );
        }else {
            $livre['auteurs'][] = array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']);
        }       
    }

    if(empty($livre)){
        $erreurs[] = '- Aucun livre ne correspond à l\'article saisie.';
        if(isset($_GET['action'])){
            unset($_GET['action']);
            header("Location: details.php?article=".$_GET['article']);
        }
        mysqli_free_result($res);
        mysqli_close($bd);
    }else{
        atl_get_action($livre,$bd);
    }
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
    
    atl_aff_livre($livre);
    // libération des ressources
    mysqli_free_result($res);
    mysqli_close($bd);
}

function atl_get_action($livre,$bd){
    //Ajout dans le panier
    if(at_creation_panier() && isset($_GET['action']) && $_GET['action']==="add"){
        at_button_ajouter_panier($livre['id'],$livre['prix'],array('article'));
    }

    //Ajout dans la wishlist
    if(isset($_GET['action']) && $_GET['action']==="addW"){
        at_ajouter_wishlist($bd,$livre['id'],array('article'));
    }
}
?>