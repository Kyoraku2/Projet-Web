<?php

ob_start(); //démarre la bufferisation
session_start();

require_once '../php/bibli_generale.php';
require_once ('../php/bibli_bookshop.php');

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)

at_aff_debut('BookShop | Panier', '../styles/bookshop.css', 'main');

at_aff_enseigne_entete();

atl_aff_contenu();

at_aff_pied();

at_aff_fin('main');

ob_end_flush();

function atl_aff_contenu(){
    if(at_creation_panier()){
        $nb_articles=at_compter_articles();
        if($nb_articles==0){
            echo '<h3>Votre panier est vide</h3>';
            return;
        }
        echo '<h3>Voici votre panier',
        (at_est_authentifie())?"":" , connectez vous pour le valider",
        ' (',$nb_articles,' article',($nb_articles>1)?"s)":")",
        '</h3>';
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
            mysqli_close($bd);
        }

        //Montant total
        echo '<p>Prix total de la commande :',at_montant_global(),' &euro;</p>';

        //Reset le panier
        echo '<p><a href="',$_SERVER['REQUEST_URI'],'?action=reset" title="Vider le panier">Réinitialiser le panier</a></p>';
        //Valider le panier
        echo '<p><a href="',$_SERVER['REQUEST_URI'],'?action=validate" title="Valider le panier">Valider le panier</a></p>';
        atl_panier_action($bd);
        mysqli_close($bd);
    }
}

function atl_panier_action($bd){
    //Retrait du panier
    if(isset($_GET['action']) && $_GET['action']==="delete"){
        at_supprimer_article($_GET['id']);
        unset($_GET['action']);
        unset($_GET['id']);
        $url=strtok($_SERVER["REQUEST_URI"], '?');
        header("Location: $url");
    } // Else + parameter controle

    //Modifier quantité
    if(isset($_GET['action']) && isset($_GET['qte']) && isset($_GET['id']) && at_est_entier($_GET['id']) && at_est_entier($_GET['qte']) &&  $_GET['action']==="change"){
        at_modifier_qte_article($_GET['id'],$_GET['qte']);
        unset($_GET['action']);
        unset($_GET['id']);
        unset($_GET['qte']);
        $url=strtok($_SERVER["REQUEST_URI"], '?');
        header("Location: $url");
    } // Else + parameter controle

    //Vider le panier
    if(isset($_GET['action']) && $_GET['action']==="reset"){
        at_supprime_panier();
        unset($_GET['action']);
        $url=strtok($_SERVER["REQUEST_URI"], '?');
        header("Location: $url");
    }

    //Valider le panier
    if(isset($_GET['action']) && $_GET['action']==="validate"){
        if(!at_est_authentifie()){
            unset($_GET['action']);
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
            echo '<p class="error">Vous n\'avez pas renseigné votre adresse de livraison.</p>';
            mysqli_free_result($res);
            return;
        }
        mysqli_free_result($res);
        //INSERT INTO clients(cliNomPrenom, cliEmail, cliDateNaissance, cliPassword, cliAdresse, cliCP, cliVille, cliPays) 
        //VALUES ('$nomprenom', '$email', $aaaammjj, '$passe1', '', 0, '', '')";
        $sql="INSERT INTO "; //Manque l'insert

        at_supprime_panier();
        header("Location: $url");
    }
}

function atl_aff_livre($livre) {
    // Le nom de l'auteur doit être encodé avec urlencode() avant d'être placé dans une URL, sans être passé auparavant par htmlentities()
    $auteurs = $livre['auteurs'];
    $livre = at_html_proteger_sortie($livre);
    echo 
        '<article class="arCart">', 
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
            'Quantité : ',at_aff_liste_nombre("qte",0,100,1, at_qte_article($livre['id']),"onchange=this.form.submit()"),
            '</form>',
            ' <a href="',$_SERVER['REQUEST_URI'],'?action=delete&id=',$livre['id'],'" title="Retirer du panier">Supprimer</a>',
            '<br>Prix total pour cet article : ',at_montant($livre['id']),' &euro;',
        '</article>';
}

//Gérer la validation des paramètres
//protéger entrée/sortie
//Gérer les condition : si id pas dans array, erreur
//Gérer la validation du panier

?>