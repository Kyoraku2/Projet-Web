<?php

ob_start(); //démarre la bufferisation
session_start();
require_once '../php/bibli_generale.php';
require_once ('../php/bibli_bookshop.php');

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)

if(!at_est_authentifie()){
    if(isset($_SERVER['HTTP_REFERER'])){
        header('Location: '.$_SERVER['HTTP_REFERER']);
    }else{
        header('Location: ../index.php');
    }
}

at_aff_debut('BookShop | Historique', '../styles/bookshop.css', 'main');

at_aff_enseigne_entete();

atl_aff_contenu();

at_aff_pied('../');

at_aff_fin('main');

ob_end_flush();

function atl_aff_contenu(){
    echo '<h1>Historique des commandes</h1>';
    $bd = at_bd_connecter();
    $id=at_bd_proteger_entree($bd,$_SESSION['id']);
    $sql ="SELECT coID,coIDClient,coDate,coHeure,ccIDCommande,ccQuantite,liID, liTitre, liPrix, liPages, liISBN13, edNom, edWeb, auNom, auPrenom 
    FROM commandes,compo_commande,livres,auteurs,aut_livre,editeurs
    WHERE coID=ccIDCommande 
    AND ccIDLivre=liID 
    AND liIDEditeur = edID
    AND al_IDLivre = liID
    AND al_IDAuteur = auID
    AND coIDClient=$id";
    $res = mysqli_query($bd, $sql) or at_bd_erreur($bd,$sql);
    /*
    allcmd = array(
        array(livres),
        array(livres)
    )
    */

    $all_commands=array();
    $lastBookID = -1;
    $lastCmdID = -1;
    while ($t = mysqli_fetch_assoc($res)) {
        if($t['coID'] != $lastCmdID){
            if ($lastCmdID != -1) {
                $all_commands[$lastCmdID]=$cmd; 
            }
            $lastCmdID = $t['coID'];
            $cmd=array('heure'=>$t['coHeure'],'date'=>$t['coDate']);
        }
    }
    if($lastCmdID != -1){
        $all_commands[]=$cmd; 
    }

    mysqli_data_seek($res,0);

    $lastCmdID=-1;
    while($t = mysqli_fetch_assoc($res)){
        if ($t['liID'] != $lastBookID) {
            if ($lastBookID != -1) {
                $all_commands[$lastCmdID][]=$livre;
            }
            if($t['coID'] != $lastCmdID){
                $lastCmdID=$t['coID'];
            }
            $lastBookID = $t['liID'];
            $livre = array( 'id' => $t['liID'],
            'titre' => $t['liTitre'],
            'edNom' => $t['edNom'],
            'edWeb' => $t['edWeb'],
            'pages' => $t['liPages'],
            'ISBN13' => $t['liISBN13'],
            'prix' => $t['liPrix'],
            'qte' => $t['ccQuantite'],
            'auteurs' => array(array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']))
            );
        }else{
            $livre['auteurs'][] = array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']);
        }
    }
    // libération des ressources
    mysqli_free_result($res);
    mysqli_close($bd);
    if ($lastBookID != -1) {
        $all_commands[$lastCmdID][]=$livre;
    }
    
    $size=count($all_commands);
    if($size===0){
        echo '<p>Vous n\'avez passé aucune commande sur bookshop</p>';
        return;
    }
    $i=0;
    foreach($all_commands as $c){
        $i++;
        echo "<h2>Commande n°$i</h2>";
        echo '<p>Passée le ',date('d/m/Y',strtotime($c['date'])),' à ',date('H\hi',strtotime($c['heure']));
        echo '<br><br>Contenu de la commande :</p>';
        $size2=count($c);
        for($j=0;$j<$size2-2;$j++){
            atl_aff_livre($c[$j]);
        }
    }
}

function atl_aff_livre($livre) {
    // Le nom de l'auteur doit être encodé avec urlencode() avant d'être placé dans une URL, sans être passé auparavant par htmlentities()
    $auteurs = $livre['auteurs'];
    $livre = at_html_proteger_sortie($livre);
    echo 
        '<article class="arCart" style="border: none;">', 
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
            'Quantité : ', $livre['qte'], 
        '</article>';
}

//redirection y'a un pb
//Voir pour toggle les contenus
?>