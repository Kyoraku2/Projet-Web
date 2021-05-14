<?php

ob_start();
session_start();

require_once '../php/bibli_generale.php';
require_once '../php/bibli_bookshop.php';

error_reporting(E_ALL);
if(!isset($_SERVER['HTTP_REFERER']) || !isset($_SESSION['tmpidlivre']) || !isset($_SESSION['tmptype']) || !isset($_SESSION['tmpback'])){
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


function atl_aff_contenu(){
    echo '<h1>Validation d\'ajout</h1>',
    '<p>L\'article suivant à bien été ajouté à votre ',($_SESSION['tmptype']==='cart')?'panier':'liste de souhait(s)',' :</p>';
    // ouverture de la connexion, requête
    $bd = at_bd_connecter();
    $id=at_bd_proteger_entree($bd,$_SESSION['tmpidlivre']);
    $sql =  "SELECT liID, liTitre, liPrix, liPages, liISBN13, edNom, edWeb, auNom, auPrenom 
            FROM livres INNER JOIN editeurs ON liIDEditeur = edID 
                        INNER JOIN aut_livre ON al_IDLivre = liID 
                        INNER JOIN auteurs ON al_IDAuteur = auID 
            WHERE liID=$id";

    $res = mysqli_query($bd, $sql) or at_bd_erreur($bd,$sql);

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
    if ($lastID === -1) {
        echo '<p>Aucun livre trouvé</p>';
    }
    atl_aff_livre($livre);
    echo '<p><a href="',$_SESSION['tmpback'],'" title="Retour à la page précédente">Retourner à la page précédente</a>',
    '<p><a href="',($_SESSION['tmptype']==='cart')?'./panier.php':'./liste.php','" title="',($_SESSION['tmptype']==='cart')?'Panier':'Liste de souhait(s)','">Aller vers ',($_SESSION['tmptype']==='cart')?'le panier':'la liste de souhait(s)','</a>';
    unset($_SESSION['tmpback']);
    unset($_SESSION['tmpidlivre']);
    unset($_SESSION['tmptype']);
}

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