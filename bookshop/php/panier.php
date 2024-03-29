<?php

ob_start(); //démarre la bufferisation
session_start();

require_once '../php/bibli_generale.php';
require_once ('../php/bibli_bookshop.php');

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)

at_aff_debut('BookShop | Panier', '../styles/bookshop.css', 'main');

at_aff_enseigne_entete();

atl_aff_contenu();

at_aff_pied('../');

at_aff_fin('main');

ob_end_flush();


// ----------  Fonctions locales au script ----------- //

/**
 *  Affiche les livres contenus dans le panier de session
 *  Possibilité de valider son panier => redirection vers validate.php
 *  Et de vider son panier
 */
function atl_aff_contenu(){
    if(at_creation_panier()){
        $nb_articles=at_compter_articles();
        if($nb_articles==0){
            echo '<h3>Votre panier est vide</h3>';
            return;
        }
        echo '<h1>Voici votre panier',
        (at_est_authentifie())?"":" , connectez vous pour le valider",
        ' (',$nb_articles,' article',($nb_articles>1)?"s)":")",
        '</h1>',
        '<hr>';
        // ouverture de la connexion, requête
        $bd = at_bd_connecter();
    
        //Récupération des articles
        $sql =  "SELECT liID, liTitre, liPrix, liPages, liISBN13, liResume, edNom, edWeb, auNom, auPrenom 
        FROM livres,editeurs,aut_livre,auteurs
        WHERE al_IDAuteur = auID
        AND al_IDLivre = liID 
        AND liIDEditeur = edID";
        
        $sql.= " AND liID IN (";
        foreach($_SESSION['panier']['idProd'] as $id){
            $id=at_bd_proteger_entree($bd,$id); 
            $sql.="$id,";
        }
        $sql=substr($sql, 0, -1);
        $sql.=')';
        
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
                'auteurs' => array(array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']))
                );
            }else{
                $livre['auteurs'][] = array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']);
            }
        }
        // libération des ressources
        if ($lastID != -1) {
            atl_aff_livre($livre); 
            mysqli_free_result($res);
        }else{
            mysqli_free_result($res);
            mysqli_close($bd);
        }

        //Montant total
        echo '<hr>','<h3>Sous-total : ',at_montant_global(),' &euro;</h3>';

        //Boutons reset/validation
        echo '<div style="width: 25%; margin:0 auto;">',
        '<a href="',$_SERVER['REQUEST_URI'],'?action=validate" title="Valider le panier">Valider</a>',
        '<a href="',$_SERVER['REQUEST_URI'],'?action=reset" title="Vider le panier">Vider</a></div>';
        atl_panier_action($bd);
        mysqli_close($bd);
    }
}

/**
 * Fonction permettant les actions de suppression, de modification de quantité, de validation du panier
 */
function atl_panier_action(){
    //Retrait du panier
    if(isset($_GET['action']) && $_GET['action']==="delete"){
        at_supprimer_article($_GET['id']);
        $url=strtok($_SERVER["REQUEST_URI"], '?');
        header("Location: $url");
        exit();
    }

    //Modifier quantité
    if(isset($_GET['action']) && isset($_GET['qte']) && isset($_GET['id']) && at_est_entier($_GET['id']) && at_est_entier($_GET['qte']) &&  $_GET['action']==="change"){
        at_modifier_qte_article($_GET['id'],$_GET['qte']);
        $url=strtok($_SERVER["REQUEST_URI"], '?');
        header("Location: $url");
        exit();
    }

    //Vider le panier
    if(isset($_GET['action']) && $_GET['action']==="reset"){
        at_supprime_panier();
        $url=strtok($_SERVER["REQUEST_URI"], '?');
        header("Location: $url");
        exit();
    }
    
    //Valider panier
    if(isset($_GET['action']) && $_GET['action']==="validate"){
        if(!at_est_authentifie()){
            header("Location: ./login.php");
            exit();
        }
        header("Location: ./validate.php");
        exit();
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
    echo 
        '<article class="arCart">', 
            '<a class="removeFromCart" href="',$_SERVER['REQUEST_URI'],'?action=delete&id=',$livre['id'],'" title="Retirer du panier"></a>',
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
            '<form name="order" action="panier.php" method="get" style="display: inline-block;">',
            '<input name="action" type="hidden" value="change">',
            '<input name="id" type="hidden" value="',$livre['id'],'">',
            'Quantité : ',at_aff_liste_nombre("qte",0,100,1, at_qte_article($livre['id']),"onchange=this.form.submit()"),
            '</form>',
            '<br><h5>Prix total pour cet article : ',at_montant($livre['id']),' &euro;</h5>',
        '</article>';
}

?>