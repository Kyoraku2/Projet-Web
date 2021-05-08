<?php

/* ------------------------------------------------------------------------------
    Architecture de la page
    - étape 1 : vérification des paramètres reçus dans l'URL
    - étape 2 : génération du code HTML de la page
------------------------------------------------------------------------------*/

ob_start(); //démarre la bufferisation
session_start();

require_once '../php/bibli_generale.php';
require_once '../php/bibli_bookshop.php';

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)

/*------------------------- Etape 1 --------------------------------------------
- vérification des paramètres reçus dans l'URL
------------------------------------------------------------------------------*/

// erreurs détectées dans l'URL
$erreurs = array();

// critères de recherche
$recherche = array('type' => 'auteur', 'quoi' => '');

if ($_GET){ // s'il y a des paramètres dans l'URL
    if (! at_parametres_controle('get', array('type', 'quoi'),array('p','t','action','id'))){
        $erreurs[] = 'L\'URL doit être de la forme "recherche.php?type=auteur&quoi=Moore".';
    }
    else{
        $oks = array('titre', 'auteur');
        if (! in_array($_GET['type'], $oks)){
            $erreurs[] = 'La valeur du "type" doit être égale à "'.implode('" ou à "', $oks).'".';
        }
        $recherche['type'] = $_GET['type'];
        $recherche['quoi'] = trim($_GET['quoi']);
        $l1 = mb_strlen($recherche['quoi'], 'UTF-8');
        if ($l1 < 2){
            $erreurs[] = 'Le critère de recherche est trop court.';
        }
        if ($l1 != mb_strlen(strip_tags($recherche['quoi']), 'UTF-8')){
            $erreurs[] = 'Le critère de recherche ne doit pas contenir de tags HTML.';
        }
    }
}

/*------------------------- Etape 2 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

at_aff_debut('BookShop | Recherche', '../styles/bookshop.css', 'main');

at_aff_enseigne_entete();

atl_aff_contenu($recherche, $erreurs);

at_aff_pied('../');

at_aff_fin('main');

// fin du script --> envoi de la page 
ob_end_flush();


// ----------  Fonctions locales au script ----------- //

/**
 *  Contenu de la page : formulaire et résultats de la recherche
 *
 * @param array  $recherche     critères de recherche (type et quoi)
 * @param array  $erreurs       erreurs détectées dans l'URL
 */
function atl_aff_contenu($recherche, $erreurs) {
    $pagination = 5;
    $totalRows = -1;
    $position = -1;
    $nb = 0;

    //-- Calcul des limites ------------------------------
    // Au 1er passage il n'y a pas de paramètres dans l'url
    // et le tableau $_GET est donc vide.

    if (isset($_GET['t']) && at_est_entier($_GET['t'])) {
        $totalRows = (int) $_GET['t'];
    }

    if (isset($_GET['p']) && at_est_entier($_GET['p'])) {
        $position = (int) $_GET['p'];
    }

    // Soit 1er passage, soit paramètres GET "modifiés"
    if ($totalRows < 0 || $position < 0) {
        $totalRows = $position = 0;
    }
    // Vérification paramètres GET valides
    if ($position >= $totalRows) {
        $totalRows = $position = 0;
    }
    echo '<h3>Recherche par une partie du nom d\'un auteur ou du titre</h3>'; 
    
    /* choix de la méthode get pour avoir la même forme d'URL lors d'une soumission du formulaire, 
    et lorsqu'on accède à la page suite à un clic sur un nom d'un auteur */
    echo '<form action="recherche.php" method="get">',
            '<p>Rechercher <input type="text" name="quoi" minlength="2" value="', at_html_proteger_sortie($recherche['quoi']), '">', 
            ' dans '; 
                at_aff_liste('type', array('auteur' => 'auteurs', 'titre' => 'titre'), $recherche['type']);
    
    echo       '<input type="submit" value="Rechercher">', // pas d'attribut name pour qu'il n'y ait pas d'élément correspondant au bouton submit dans l'URL
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

    if ($recherche['quoi']){ //si recherche à faire en base de données
    
        // ouverture de la connexion, requête
        $bd = at_bd_connecter();
        
        $q = at_bd_proteger_entree($bd, $recherche['quoi']); 
        
        if ($recherche['type'] == 'auteur') {
            $critere = " WHERE liID in (SELECT al_IDLivre FROM aut_livre INNER JOIN auteurs ON al_IDAuteur = auID WHERE auNom LIKE '%$q%')";
        } 
        else {
            $critere = " WHERE liTitre LIKE '%$q%'";    
        }

        $sql =  "SELECT liID, liTitre, liPrix, liPages, liISBN13, edNom, edWeb, auNom, auPrenom 
                FROM livres INNER JOIN editeurs ON liIDEditeur = edID 
                            INNER JOIN aut_livre ON al_IDLivre = liID 
                            INNER JOIN auteurs ON al_IDAuteur = auID 
                $critere
                ORDER BY liID";
        // Si pas 1er passage : ajoute clause LIMIT
        if ($totalRows > 0) {
            $sql .= " LIMIT $position, $pagination";
        }
        $res = mysqli_query($bd, $sql) or at_bd_erreur($bd,$sql);
        // 1er passage : récup du nombre total de livres
        if ($totalRows==0) {
            $totalRows = mysqli_num_rows($res);
        }

        $livres=array();
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
                array_push($livres,$livre);
                // Ce test est nécessaire pour la 1ere page 
                $nb ++;
                echo $nb;
                if ($nb >= $pagination) {
                    break;
                } 
            }else{
                $livre['auteurs'][] = array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']);
            }
        }
        // libération des ressources
        mysqli_free_result($res);
        if ($lastID != -1) {
            atl_aff_livre($livre); 
        }else{
            echo '<p>Aucun livre trouvé</p>';
        }

        echo 'nb ',$nb,' ';
        echo 'pagination ',$pagination,' ';
        echo 'totalRows ',$totalRows,' ';
        //echo 'totalNoDup ',$totalNoDup,' ';
        echo 'position ',$position,' ';
        //-- Affichage pagination ---------------------------
        echo '<p class="pagination">Pages : ';
        for ($i = 0, $nb = 0; $i < $totalRows; $i += $pagination) {
            $nb ++;
            if ($i == $position) {  // page en cours, pas de lien
                echo "$nb ";
            } else {
                echo '<a href="', $_SERVER['PHP_SELF'],
                    '?type=',$recherche['type'],'&quoi=',$recherche['quoi'],'&t=', $totalRows, '&p=', $i, '">', 
                    $nb, '</a> ';
            }
        }
        echo '</p>';
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
            
            unset($_GET['action']);
            if($id!==-1){
                at_ajouter_article($_GET['id'],1,$livres[$id]['prix']);
                unset($_GET['action']);
                if(isset($_SERVER['HTTP_REFERER'])){
                    header("Location: ".$_SERVER['HTTP_REFERER']);
                }else{
                    header("Location: ".strtok($_SERVER['REQUEST_URI'],'?').isset($recherche['type'])?"?type=".$recherche['type']."&quoi=".$recherche['quoi']:"");
                }
            }
            $url=strtok($_SERVER['REQUEST_URI'],'?');
            $url.=isset($recherche['type'])?"?type=".$recherche['type']."&quoi=".$recherche['quoi']:"";
            header("Location: $url");
            
            
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
            }
            if(isset($_SERVER['HTTP_REFERER'])){
                header("Location: ".$_SERVER['HTTP_REFERER']);
            }else{
                $url=strtok($_SERVER['REQUEST_URI'],'?');
                $url.=isset($recherche['type'])?"?type=".$recherche['type']."&quoi=".$recherche['quoi']:"";
                header("Location: $url");
            }
        }
        mysqli_close($bd);
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
            '<a class="addToCart" href="',$_SERVER['REQUEST_URI'],'&action=add&id=',$livre['id'],'" title="Ajouter au panier"></a>',
            '<a class="addToWishlist"  href="',$_SERVER['REQUEST_URI'],'&action=addW&id=',$livre['id'],'" title="Ajouter à la liste de cadeaux"></a>',
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
