<?php

ob_start(); //démarre la bufferisation
session_start();
require_once '../php/bibli_generale.php';
require_once ('../php/bibli_bookshop.php');

error_reporting(E_ALL);
/*if(!isset($_SESSION['HTTP_REFERER']) || !at_est_authentifie()){
    header('Location: ../index.php');
    exit();
}*/

at_aff_debut('BookShop | Validation Panier', '../styles/bookshop.css', 'main');

at_aff_enseigne_entete();

atl_aff_contenu();

at_aff_pied('../');

at_aff_fin('main');

// fin du script --> envoi de la page 

ob_end_flush();

function atl_aff_contenu(){
    echo '<h1>Merci pour votre achat !</h1>',
        '<h2>Récapitulatif</h2>';

    if(at_creation_panier()){
        if(at_compter_articles()==0){
            header('Location: ../index.php');
            exit();
        }
        // ouverture de la connexion, requête
        $bd = at_bd_connecter();
    
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
        }
        atl_valider_commande($bd);
        mysqli_close($bd);
        echo '<p><a href="../index.php" title="Retour vers index">Retour à l\'acceuil</a></p>';
    }
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
            '<br><br><br>',
            '<form name="order" action="panier.php" method="get" style="display: inline-block;">',
            '<input name="action" type="hidden" value="change">',
            '<input name="id" type="hidden" value="',$livre['id'],'">',
            'Quantité :',at_qte_article($livre['id']),
            '</form>',
            '<br>Prix total pour cet article : ',at_montant($livre['id']),' &euro;',
        '</article>';
}


function atl_valider_commande($bd){
    //Valider le panier
    if(!at_est_authentifie()){
        header("Location: ./login.php");
        return;
    }
    unset($_GET['action']);
    $id=$_SESSION['id'];//at_bd_proteger_entree($bd, $_SESSION['id']);
    $sql="SELECT cliID,cliAdresse
    FROM clients
    WHERE cliID = $id";

    $res = mysqli_query($bd,$sql) or at_bd_erreur($bd,$sql);

    if(mysqli_num_rows($res) == 0) {
        echo 'Vous n\'avez pas renseigné votre adresse de livraison !';
        mysqli_free_result($res);
        return;
    }
    $row=mysqli_fetch_assoc($res);
    if(empty(trim($row['cliAdresse']))){
        echo '<p class="error">Vous n\'avez pas renseigné votre adresse de livraison.
        <br>Cliquez <a href="./compte.php" title="Accès à la page compte">ici</a> pour la renseigner.</p>';
        mysqli_free_result($res);
        return;
    }
    mysqli_free_result($res);
    $id=at_bd_proteger_entree($bd,$_SESSION['id']);
    $date=date("Ymd");
    $date=at_bd_proteger_entree($bd,$date);
    $hour=date("Hi");
    $hour=at_bd_proteger_entree($bd,$hour);
    $sql="INSERT INTO `commandes` (`coIDClient`, `coDate`, `coHeure`) VALUES
    ($id,$date,$hour)";

    $res = mysqli_query($bd,$sql) or at_bd_erreur($bd,$sql);
    $id_cmd=mysqli_insert_id($bd);

    $sql="INSERT INTO `compo_commande` (`ccIDCommande`, `ccIDLivre`, `ccQuantite`) VALUES";
    foreach($_SESSION['panier']['idProd'] as $prod){
        $qte=at_qte_article($prod);
        $sql.="($id_cmd,$prod,$qte),";
    }
    $sql=mb_substr($sql, 0, -1);
    $res = mysqli_query($bd,$sql) or at_bd_erreur($bd,$sql);

    at_supprime_panier();
}
?>