<?php

date_default_timezone_set('Europe/Paris');
ob_start(); //démarre la bufferisation
session_start();
require_once '../php/bibli_generale.php';
require_once ('../php/bibli_bookshop.php');

error_reporting(E_ALL);

at_aff_debut('BookShop | Validation Panier', '../styles/bookshop.css', 'main');

at_aff_enseigne_entete();

atl_aff_contenu();

at_aff_pied('../');

at_aff_fin('main');

// fin du script --> envoi de la page 

ob_end_flush();

/**
 *  Contenu de la page : Récapitulatif de commande, bouton de redirection
 */
function atl_aff_contenu(){
    if(at_creation_panier()){
        if(at_compter_articles()==0){
            header('Location: ../index.php');
            exit();
        }
        // ouverture de la connexion, requête
        $bd = at_bd_connecter();

        $id=at_bd_proteger_entree($bd,$_SESSION['id']);
        
        
        //Vérification de l'adresse de livraison
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
        

        //Validation de commande
        $id_cmd=atl_valider_commande($bd);
        $id_cmd=at_bd_proteger_entree($bd,$id_cmd);

        echo '<h1>Merci pour votre achat !</h1>',
        '<h2>Récapitulatif</h2>';

        $sql =  "SELECT coID,coIDClient,coDate,coHeure,ccIDCommande,ccQuantite,liID, liTitre, liPrix, liPages, liISBN13, edNom, edWeb, auNom, auPrenom 
        FROM commandes,compo_commande,livres,auteurs,aut_livre,editeurs,clients
        WHERE coID=ccIDCommande 
        AND ccIDLivre=liID 
        AND liIDEditeur = edID
        AND al_IDLivre = liID
        AND al_IDAuteur = auID
        AND cliID=coIDClient
        AND coIDClient=$id
        AND coID=$id_cmd";

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
        mysqli_close($bd);
        mysqli_free_result($res);
        if ($lastID != -1) {
            atl_aff_livre($livre); 
        }

        $montant=at_montant_global();
        echo '<h3>Sous-total : ',$montant,' &euro;</h3>',
        '<div style="width: 15%; margin:1em auto;">',
        '<p><a href="../index.php" title="Retour vers index">Retour</a></p>',
        '</div>';
        at_supprime_panier();
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
            'Quantité :',at_qte_article($livre['id']),
            '<br><h5>Prix total pour cet article : ',at_montant($livre['id']),' &euro;</h5>',
        '</article>';
}

/**
 * Validation de commande : 
 * - envoie de la commande à la BD
 * 
 * @param object  $bd   Lien vers la BD
 */
function atl_valider_commande($bd){
    if(!at_est_authentifie()){
        header("Location: ./login.php");
        exit();
    }

    $id=at_bd_proteger_entree($bd,$_SESSION['id']);
    $date=date("Ymd");
    $date=at_bd_proteger_entree($bd,$date);
    $hour=date("Hi");
    $hour=at_bd_proteger_entree($bd,$hour);

    $sql="INSERT INTO `commandes` (`coIDClient`, `coDate`, `coHeure`) VALUES
    ($id,$date,$hour)";
    //$sql="INSERT INTO `commandes` (`coIDClient`, `coDate`, `coHeure`)
    //SELECT $id,$date,$hour WHERE EXISTS (SELECT cliAdresse FROM clients WHERE cliID=$id)";

    $res = mysqli_query($bd,$sql) or at_bd_erreur($bd,$sql);
    $id_cmd=mysqli_insert_id($bd);

    $sql="INSERT INTO `compo_commande` (`ccIDCommande`, `ccIDLivre`, `ccQuantite`) VALUES";
    foreach($_SESSION['panier']['idProd'] as $prod){
        $qte=at_qte_article($prod);
        $sql.="($id_cmd,$prod,$qte),";
    }
    $sql=mb_substr($sql, 0, -1);
    $res = mysqli_query($bd,$sql) or at_bd_erreur($bd,$sql);
    return $id_cmd;
}
?>