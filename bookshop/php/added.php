<?php

ob_start();
session_start();

require_once '../php/bibli_generale.php';
require_once '../php/bibli_bookshop.php';

error_reporting(E_ALL);

//Si les informations concernant le livre ajouté ne sont pas dans la variable de session, l'utilisateur est retourné vers la page d'acceuil
if(!isset($_SESSION['tmpidlivre']) || !isset($_SESSION['tmptype']) || !isset($_SESSION['tmpback'])){
    header('Location: ../index.php');
    exit();
}

at_aff_debut('BookShop | Article ajouté', '../styles/bookshop.css', 'main');

at_aff_enseigne_entete();

atl_aff_contenu();

at_aff_pied('../');

at_aff_fin('main');

// fin du script --> envoi de la page 
ob_end_flush();


/**
 * Permet l'affichage du contenu de la page
 *  - Le livre correspondant aux informations est récupéré dans la bd
 *  - Il est affiché accompagné d'un message de validation
 *  - Deux liens guide l'utilisateur soit vers la panier, soit vers la page d'où il vient
 *  - Les variables de session temporaires sont supprimées 
 */
function atl_aff_contenu(){
    // ouverture de la connexion, requête
    $bd = at_bd_connecter();
    $id=at_bd_proteger_entree($bd,$_SESSION['tmpidlivre']);
    $sql =  "SELECT liID, liTitre, liPrix, liPages, liISBN13, edNom, edWeb, auNom, auPrenom 
            FROM livres INNER JOIN editeurs ON liIDEditeur = edID 
                        INNER JOIN aut_livre ON al_IDLivre = liID 
                        INNER JOIN auteurs ON al_IDAuteur = auID 
            WHERE liID=$id";

    $res = mysqli_query($bd, $sql) or at_bd_erreur($bd,$sql);
    //Si aucun résultat, suppression et redirection
    if(mysqli_num_rows($res)===0){
        $tmp=$_SESSION['tmpback'];
        unset($_SESSION['tmpback']);
        unset($_SESSION['tmpidlivre']);
        unset($_SESSION['tmptype']);
        header('Location: '.$tmp);
        exit();
    }
    $lastID = -1;
    while ($t = mysqli_fetch_assoc($res)) {
        if ($t['liID'] != $lastID) {
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
    mysqli_free_result($res);
    mysqli_close($bd);

    echo '<h1>Validation d\'ajout</h1>',
    '<p>L\'article suivant à bien été ajouté à votre ',($_SESSION['tmptype']==='cart')?'panier':'liste de souhait(s)',' :</p>';
    atl_aff_livre($livre);
    echo '<div style="width: 25%; margin:1em auto;">',
    '<a href="',($_SESSION['tmptype']==='cart')?'./panier.php':'./liste.php','" title="',($_SESSION['tmptype']==='cart')?'Aller vers le Panier':'Aller vers la Liste de souhait(s)','">',($_SESSION['tmptype']==='cart')?'Panier':'Souhaits','</a>',
    '<a href="',$_SESSION['tmpback'],'" title="Retour à la page précédente">Retour</a>',
    '</div>';
    
    //Suppression des variables temporaires
    unset($_SESSION['tmpback']);
    unset($_SESSION['tmpidlivre']);
    unset($_SESSION['tmptype']);
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
        '<article class="arRecherche">', 
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
?>