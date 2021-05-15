<?php

/*********************************************************
 *        Bibliothèque de fonctions spécifiques          *
 *               à l'application BookShop                *
 *********************************************************/

/** Constantes : les paramètres de connexion au serveur MySQL */
define ('BD_SERVER', 'localhost');

define ('BD_NAME', 'bookshop_db');
define ('BD_USER', 'bookshop_user');
define ('BD_PASS', 'bookshop_pass');

/*define ('BD_NAME', 'merlet_bookshop');
define ('BD_USER', 'merlet_u');
define ('BD_PASS', 'merlet_p');*/

define('LMAX_EMAIL', 50); //longueur du champ dans la base de données
define('LMAX_NOMPRENOM', 100); //longueur du champ dans la base de données

// paramètres de l'application
define('LMIN_PASSWORD', 4);
define('LMAX_PASSWORD', 20);

define('NB_ANNEE_DATE_NAISSANCE', 120);
 
define('LMAX_ADRESSE', 100); //longueur du champ dans la base de données
define('L_CP', 5);
define('LMAX_VILLE', 50);
define('LMAX_PAYS', 50);
/**
 *  Fonction affichant l'enseigne et le bloc entête avec le menu de navigation.
 *
 *  @param  string      $prefix     Prefixe des chemins vers les fichiers du menu (usuellement "./" ou "../").
 */
function at_aff_enseigne_entete($prefix = '../') {
    echo 
        '<aside>',
            '<a href="http://www.facebook.com" target="_blank"></a>',
            '<a href="http://www.twitter.com" target="_blank"></a>',
            '<a href="http://plus.google.com" target="_blank"></a>',
            '<a href="http://www.pinterest.com" target="_blank"></a>',
        '</aside>',
        
        '<header>';
    
    at_aff_menu($prefix);
    echo    '<img src="', $prefix,'images/soustitre.png" alt="sous titre">',
        '</header>';
}


/**
 *  Fonction affichant le menu de navigation de l'application BookShop 
 *
 *  @param  string      $prefix     Prefixe des chemins vers les fichiers du menu (usuellement "./" ou "../").
 */
function at_aff_menu($prefix) {      
    echo '<nav>',    
            '<a href="', $prefix, 'index.php" title="Retour à la page d\'accueil"></a>';
    
    $liens = array( 'recherche'   => array( 'position' => 1, 'title' => 'Effectuer une recherche'),
                    'panier'      => array( 'position' => 2, 'title' => 'Voir votre panier'),
                    'liste'       => array( 'position' => 3, 'title' => 'Voir une liste de cadeaux'),
                    'compte'      => array( 'position' => 4, 'title' => 'Consulter votre compte'),
                    'deconnexion' => array( 'position' => 5, 'title' => 'Se déconnecter'));
                    
    if (! at_est_authentifie()){
        unset($liens['compte']);
        unset($liens['deconnexion']);
        ++$liens['recherche']['position'];
        ++$liens['panier']['position'];
        ++$liens['liste']['position'];
        /*TODO :    - peut-on implémenter les 3 incrémentations ci-dessus avec un foreach ? */
        $liens['login'] = array( 'position' => 5, 'title' => 'Se connecter');
        /* Debug :
        echo '<pre>', print_r($liens, true), '</pre>';
        exit;*/
    }
    
    foreach ($liens as $cle => $elt) {
        echo '<a class="pos', $elt['position'], '" href="', $prefix, 'php/', $cle, '.php" title="', $elt['title'], '"></a>';
    }
    echo '</nav>';
}


/**
 *  Fonction affichant le pied de page de l'application BookShop.
 */
function at_aff_pied($prefix='./') {
    echo 
        '<footer>', 
            'BookShop &amp; Partners &copy; ', date('Y'), ' - ',
            '<a href="',$prefix,'php/about.php">A propos</a> - ',
            '<a href="',$prefix,'php/confident.php">Emplois @ BookShop</a> - ',
            '<a href="',$prefix,'php/conditions.php">Conditions d\'utilisation</a>',
        '</footer>';
}

//_______________________________________________________________
/**
* Détermine si l'utilisateur est authentifié
*
* @global array    $_SESSION 
* @return boolean  true si l'utilisateur est authentifié, false sinon
*/
function at_est_authentifie() {
    return  isset($_SESSION['id']);
}

//_______________________________________________________________
/**
 * Termine une session et effectue une redirection vers la page transmise en paramètre
 *
 * Elle utilise :
 *   -   la fonction session_destroy() qui détruit la session existante
 *   -   la fonction session_unset() qui efface toutes les variables de session
 * Elle supprime également le cookie de session
 *
 * Cette fonction est appelée quand l'utilisateur se déconnecte "normalement" et quand une 
 * tentative de piratage est détectée. On pourrait améliorer l'application en différenciant ces
 * 2 situations. Et en cas de tentative de piratage, on pourrait faire des traitements pour 
 * stocker par exemple l'adresse IP, etc.
 * 
 * @param string    URL de la page vers laquelle l'utilisateur est redirigé
 */
function at_session_exit($page = '../index.php') {
    session_destroy();
    session_unset();
    $cookieParams = session_get_cookie_params();
    setcookie(session_name(), 
            '', 
            time() - 86400,
            $cookieParams['path'], 
            $cookieParams['domain'],
            $cookieParams['secure'],
            $cookieParams['httponly']
        );
    header("Location: $page");
    exit();
}


function at_creation_panier(){
    if (!isset($_SESSION['panier'])){
       $_SESSION['panier']=array();
       $_SESSION['panier']['idProd'] = array();
       $_SESSION['panier']['qteProduit'] = array();
       $_SESSION['panier']['prixProduit'] = array();
    }
    return true;
 }

 function at_ajouter_article($idProd,$qteProduit,$prixProduit){
    //Si le panier existe
    if (at_creation_panier()){
        //Si le produit existe déjà on ajoute seulement la quantité
        $positionProduit = array_search($idProd,  $_SESSION['panier']['idProd']);
        if ($positionProduit !== false){
           $_SESSION['panier']['qteProduit'][$positionProduit] += $qteProduit ;
        }else{
            //Sinon on ajoute le produit
            array_push( $_SESSION['panier']['idProd'],$idProd);
            array_push( $_SESSION['panier']['qteProduit'],$qteProduit);
            array_push( $_SESSION['panier']['prixProduit'],$prixProduit);
        }
    }else{
        echo "Un problème est survenu veuillez contacter l'administrateur du site.";
    }
}

function at_supprimer_article($idProd){
    //Si le panier existe
    if (at_creation_panier()){
       //Nous allons passer par un panier temporaire
       $tmp=array();
       $tmp['idProd'] = array();
       $tmp['qteProduit'] = array();
       $tmp['prixProduit'] = array();
 
       for($i = 0; $i < count($_SESSION['panier']['idProd']); $i++){
            if ($_SESSION['panier']['idProd'][$i] !== $idProd){
                array_push( $tmp['idProd'],$_SESSION['panier']['idProd'][$i]);
                array_push( $tmp['qteProduit'],$_SESSION['panier']['qteProduit'][$i]);
                array_push( $tmp['prixProduit'],$_SESSION['panier']['prixProduit'][$i]);
            }
        }
        //On remplace le panier en session par notre panier temporaire à jour
        $_SESSION['panier'] =  $tmp;
        //On efface notre panier temporaire
        unset($tmp);
    }else{
        echo "Un problème est survenu veuillez contacter l'administrateur du site."; 
    }
}

function at_modifier_qte_article($idProd,$qteProduit){
    //Si le panier existe
    if (at_creation_panier()){
       //Si la quantité est positive on modifie sinon on supprime l'article
        if ($qteProduit > 0){
            //Recherche du produit dans le panier
            $positionProduit = array_search($idProd,  $_SESSION['panier']['idProd']);
            if ($positionProduit !== false){
                $_SESSION['panier']['qteProduit'][$positionProduit] = $qteProduit ;
            }
        }else{
            at_supprimer_article($idProd);
        }
    }else{
        echo "Un problème est survenu veuillez contacter l'administrateur du site.";
    }
}

function at_qte_article($idProd){
    //Recherche du produit dans le panier
    $positionProduit = array_search($idProd,  $_SESSION['panier']['idProd']);
    if ($positionProduit !== false){
        return $_SESSION['panier']['qteProduit'][$positionProduit];
    }
    return 0;
}

function at_montant_global(){
    $total=0;
    $size=count($_SESSION['panier']['idProd']);
    for($i = 0; $i < $size; $i++){
       $total += $_SESSION['panier']['qteProduit'][$i]*$_SESSION['panier']['prixProduit'][$i];
    }
    return $total;
}

function at_montant($idProd){
    //Si le panier existe
    $montant=0;
    if (at_creation_panier()){
        //Si le produit existe déjà
        $positionProduit = array_search($idProd,  $_SESSION['panier']['idProd']);
        if ($positionProduit !== false){
           $montant=$_SESSION['panier']['qteProduit'][$positionProduit]*$_SESSION['panier']['prixProduit'][$positionProduit];
        }
    }else{
        echo "Un problème est survenu veuillez contacter l'administrateur du site.";
    }
    return $montant;
}

function at_compter_articles(){
    if (isset($_SESSION['panier'])){
        $total=0;
        $size=count($_SESSION['panier']['idProd']);
        for($i = 0; $i < $size; $i++){
            $total += $_SESSION['panier']['qteProduit'][$i];
        }
        return $total;
    }
    return 0;
}

function at_supprime_panier(){
    unset($_SESSION['panier']);
}

function at_button_ajouter_panier($id,$prix,$prefix='./',$cles_facultatives = array()){
    at_ajouter_article($id,1,$prix);
    unset($_GET['action']);
    at_redirections_after_add('cart',$prefix,$cles_facultatives,$id);
}

function at_ajouter_wishlist($bd,$idl,$prefix='./',$cles_facultatives = array()){
    if(!at_est_authentifie()){
        unset($_GET['action']);
        header('Location: '.$prefix.'login.php');
        return;
    }
    //Check for duplicate or non existant
    $id_livre=at_bd_proteger_entree($bd,$idl);
    $id_client=at_bd_proteger_entree($bd,$_SESSION['id']);
    $sql="INSERT INTO listes (listIDClient,listIDLivre) SELECT $id_client,$id_livre
    WHERE NOT EXISTS (SELECT * FROM listes WHERE listIDClient=$id_client AND listIDLivre=$id_livre)
    AND EXISTS (SELECT * FROM livres WHERE liID=$id_livre)";
    $res = mysqli_query($bd, $sql) or at_bd_erreur($bd,$sql);
    $insert=(mysqli_num_rows($res)==0)?1:0;
    mysqli_free_result($res);
    unset($_GET['action']);
    at_redirections_after_add('wishlist',$prefix,$cles_facultatives,$idl);
}

function at_redirections_after_add($type,$prefix,$cles_facultatives,$id){
    if(isset($_SERVER['HTTP_REFERER'])){
        $_SESSION['tmpback']=$_SERVER['HTTP_REFERER'];
        $_SESSION['tmpidlivre']=$id;
        $_SESSION['tmptype']=$type;
        header('Location: '.$prefix.'added.php');
    }else{
        $url=strtok($_SERVER['REQUEST_URI'],'?');
        if(!empty($cles_facultatives)){
            $url.='?';
            foreach($cles_facultatives as $key){
                if(isset($_GET[$key])){
                    $url.=$key;
                    $url.='=';
                    $url.=$_GET[$key];
                    $url.='&';
                }
            }
            $url=mb_substr($url, 0, -1);
        }
        $_SESSION['tmpback']=$url;
        $_SESSION['tmpidlivre']=$id;
        $_SESSION['tmptype']=$type;
        header('Location: '.$prefix.'added.php');
    }
}
?>
