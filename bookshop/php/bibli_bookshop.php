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

//___________________________________________________________________
/**
 * Fonction permettant de créer un panier
 * La panier sera contenu dans la variable de session, ne nécessitant pas d'être connecté
 * pour pouvoir le remplir, vider, modifier
 * 
 * @return boolean Si le panier n'existe pas il est créé puis true est retourné, si il existe la fonction retourne simplement true
 *
 */
function at_creation_panier(){
    if (!isset($_SESSION['panier'])){
       $_SESSION['panier']=array();
       $_SESSION['panier']['idProd'] = array();
       $_SESSION['panier']['qteProduit'] = array();
       $_SESSION['panier']['prixProduit'] = array();
    }
    return true;
 }

//___________________________________________________________________
/**
 * Permet d'ajouter un article à son panier en fonction de son id, la quantité initiale et le prix de ce produit
 * 
 * @param int    idProd       L'id du produit dans la BD (aussi contenu dans le panier)
 * @param int    qteProduit   La quantité initiale lors de l'ajout, ici égale à 1 de manière générale
 * @param float  prixProduit  Le prix associé au produit
 */
 function at_ajouter_article($idProd,$qteProduit,$prixProduit){
    if (at_creation_panier()){
        //Si le produit existe déjà dans le panier, on ajoute 1 à la quantité
        $positionProduit = array_search($idProd,  $_SESSION['panier']['idProd']);
        if ($positionProduit !== false){
           $_SESSION['panier']['qteProduit'][$positionProduit] += $qteProduit ;
        }else{
            //Sinon on ajoute le produit
            array_push( $_SESSION['panier']['idProd'],$idProd);
            array_push( $_SESSION['panier']['qteProduit'],$qteProduit);
            array_push( $_SESSION['panier']['prixProduit'],$prixProduit);
        }
    }
}

//___________________________________________________________________
/**
 * Supprime un article du panier
 * 
 * @param int    idProd       L'id du produit dans la BD (aussi contenu dans le panier)
 */
function at_supprimer_article($idProd){
    if (at_creation_panier()){
        //Panier temporaire qui contiendra tous les produits à garder
        $tmp=array();
        $tmp['idProd'] = array();
        $tmp['qteProduit'] = array();
        $tmp['prixProduit'] = array();
        for($i = 0, $nb=count($_SESSION['panier']['idProd']); $i < $nb; $i++){
            if ($_SESSION['panier']['idProd'][$i] !== $idProd){
                array_push( $tmp['idProd'],$_SESSION['panier']['idProd'][$i]);
                array_push( $tmp['qteProduit'],$_SESSION['panier']['qteProduit'][$i]);
                array_push( $tmp['prixProduit'],$_SESSION['panier']['prixProduit'][$i]);
            }
        }
        //Contient tous les livres sauf celui d'id $idProd
        $_SESSION['panier'] =  $tmp;
        unset($tmp);
    }
}

//___________________________________________________________________
/**
 * Modifie la quantité d'un produit donné dans le panier
 * 
 * @param int    idProd       L'id du produit dans la BD (aussi contenu dans le panier)
 * @param int    qteProduit   La quantité modifiée. Si <=0 : suppression de l'article, sinon la quantité est mise à jour
 */
function at_modifier_qte_article($idProd,$qteProduit){
    if (at_creation_panier()){
        if ($qteProduit > 0){
            $positionProduit = array_search($idProd,  $_SESSION['panier']['idProd']);
            if ($positionProduit !== false){
                $_SESSION['panier']['qteProduit'][$positionProduit] = $qteProduit ;
            }
        }else{
            at_supprimer_article($idProd);
        }
    }
}

//___________________________________________________________________
/**
 * Accès à la quantité d'un produit donné dans le panier
 * Notamment utile dans le récapitulatif de commandes
 * 
 * @param  int    idProd      L'id du produit dans la BD (aussi contenu dans le panier)
 * @return int                La quantité du produit correspondant, 0 si il n'est pas présent dans le panier
 */
function at_qte_article($idProd){
    //Recherche du produit dans le panier
    if (at_creation_panier()){
        $positionProduit = array_search($idProd,  $_SESSION['panier']['idProd']);
        if ($positionProduit !== false){
            return $_SESSION['panier']['qteProduit'][$positionProduit];
        }
    }
    return 0;
}

//___________________________________________________________________
/**
 * Calcul du montant global du panier
 * Notammant utile dans les affichage de prix total lors de la confection du panier
 * par l'utilisateur
 * 
 * @return int   Le montant global du panier, 0 si vide
 */
function at_montant_global(){
    $total=0;
    if (at_creation_panier()){
        $size=count($_SESSION['panier']['idProd']);
        for($i = 0; $i < $size; $i++){
            $total += $_SESSION['panier']['qteProduit'][$i]*$_SESSION['panier']['prixProduit'][$i];
        }
    }
    return $total;
}

//___________________________________________________________________
/**
 * Calcul du montant total pour un article donné
 * En fonction de son id, le montant correspont à la quantité associée à cet id multipliée
 * par le prix associé à cet id lui aussi
 * 
 * @param  int    idProd      L'id du produit dans la BD (aussi contenu dans le panier)
 * @return int                Le montant total pour un produit du panier, 0 si non présent dans le panier
 */
function at_montant($idProd){
    $montant=0;
    if (at_creation_panier()){
        $positionProduit = array_search($idProd,  $_SESSION['panier']['idProd']);
        if ($positionProduit !== false){
           $montant=$_SESSION['panier']['qteProduit'][$positionProduit]*$_SESSION['panier']['prixProduit'][$positionProduit];
        }
    }
    return $montant;
}

//___________________________________________________________________
/**
 * Permet de compter le nombre total d'article dans le panier
 * Pratique pour afficher à l'utilisateur combien d'articles composent son panier
 * 
 * @return int     Le nombre d'article dans le panier, 0 si vide
 */

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

//___________________________________________________________________
/**
 * Permet de vider un panier
 */
function at_supprime_panier(){
    unset($_SESSION['panier']);
}

//___________________________________________________________________
/**
 * Permet d'ajouter un produit au panier suite à l'action d'un utilisateur
 * Dans un premier temps, l'article est ajouté à la variable $_SESSION
 * Ensuite l'utilisateur est redirigé là d'où il vient ou vers le panier, selon son choix
 * 
 * @param int      id                 L'id du produit dans la BD (aussi contenu dans le panier)
 * @param int      prix               Le prix du produix
 * @param string   prefix             Permet de situer le répertoire de la page dans laquelle l'action à eu lieu
 * @param array    cles_facultatives  Ensemble de clés de la querystring, utilisé lors des redirections  
 */

function at_button_ajouter_panier($id,$prix,$prefix='./',$cles_facultatives = array()){
    at_ajouter_article($id,1,$prix);
    at_redirections_after_add('cart',$prefix,$cles_facultatives,$id);
}

//___________________________________________________________________
/**
 * Permet d'ajouter un produit à la liste de souhaits
 * Dans un premier temps, il faut que l'utilisateur soit connecté
 * Ensuite, si c'est le cas le produit est ajouté dans la table listes de la BD
 * ,sinon il est redirigé vers la page de connexion 
 * Enfin, il est redirigé de la même manière que lors d'un ajout dans le panier
 * 
 * @param object   bd                 Lien vers la BD
 * @param int      idl                L'id du produit dans la BD (aussi contenu dans le panier)
 * @param string   prefix             Permet de situer le répertoire de la page dans laquelle l'action à eu lieu
 * @param array    cles_facultatives  Ensemble de couple de la querystring, utilisé lors des redirections  
 */

function at_ajouter_wishlist($bd,$idl,$prefix='./',$cles_facultatives = array()){
    if(!at_est_authentifie()){
        header('Location: '.$prefix.'login.php');
        exit();
    }
    //Vérifie aussi si l'article est déjà présent dans la liste
    //De sorte à ne pas ajouiter de doublons
    $id_livre=at_bd_proteger_entree($bd,$idl);
    $id_client=at_bd_proteger_entree($bd,$_SESSION['id']);
    $sql="INSERT INTO listes (listIDClient,listIDLivre) SELECT $id_client,$id_livre
    WHERE NOT EXISTS (SELECT * FROM listes WHERE listIDClient=$id_client AND listIDLivre=$id_livre)
    AND EXISTS (SELECT * FROM livres WHERE liID=$id_livre)";
    $res = mysqli_query($bd, $sql) or at_bd_erreur($bd,$sql);
    $insert=(mysqli_num_rows($res)==0)?1:0;
    mysqli_free_result($res);
    at_redirections_after_add('wishlist',$prefix,$cles_facultatives,$idl);
}

//___________________________________________________________________
/**
 * Permet de régiriger l'utilisateur vers la page de validation d'ajout
 * Si $_SERVER['HTTP_REFERER'] est définit, la variable est utilisée
 * Sinon une url est construite en se basant sur les clés_facultatives et la page courante
 * 
 * Pour ce qui est de la redirection en elle même, 3 choses sont sauvegardées dans la varibale
 * super globale $_SESSION de manière temporaire : (de sorte à éviter tout problème avec la querystring et les utilisateurs)
 * tmpback : la page précedente qui sera utile dans l'option "retour" de la page de validation
 * tmpidlivre : l'id du livre ajouté pour pouvoir l'afficher
 * tmptype : qui indique si l'ajout à été fait dans la liste de souhaits ou dans le panier
 * Une fois sur la page de validation et le traitement fait, ces variables temporaires sont immédiatement supprimées
 * 
 * @param string   type               L'endroit où a été ajouté le produit : panier ou liste de souhaits
 * @param string   prefix             Permet de situer le répertoire de la page dans laquelle l'action à eu lieu
 * @param array    cles_facultatives  Ensemble de couple de la querystring, utilisé lors des redirections  
 * @param int      id                 L'id du produit dans la BD (aussi contenu dans le panier)
 */

function at_redirections_after_add($type,$prefix,$cles_facultatives,$id){
    if(isset($_SERVER['HTTP_REFERER'])){
        $_SESSION['tmpback']=$_SERVER['HTTP_REFERER'];
        $_SESSION['tmpidlivre']=$id;
        $_SESSION['tmptype']=$type;
        header('Location: '.$prefix.'added.php');
        exit();
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
        exit();
    }
}
?>
