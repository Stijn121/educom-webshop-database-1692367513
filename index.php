<?php
include_once 'sessions.php';
include_once "product.service.php";
include 'login.php';
include 'register.php';
include 'changepassword.php';
$page = GetRequestedPage();
$data = ProcessRequest($page);
ShowResponsePage($data);

function ProcessRequest($page){
    $data['genericErr'] = "";
    switch ($page){
        case 'register':
            $data = CheckRegister();
            if($data['registervalid']){
                try{
                StoreUser($data['email'], $data['name'], $data['password'], $data['databaseErr']);
                $page = 'login';
                $data['loginvalid'] = "";
                }
                catch(Exception $e){
                    $data['genericErr'] = 'sorry er is een technische storing1';
                    echo ("Store Userfailed, " . $e->getMessage());
                }
            }
            break;
        case 'login':
            $data = CheckLogin();
            if($data['loginvalid']){
                LoginUser($data);
                $page = 'home';
            }
            break;
        case 'logout':
            LogoutUser();
            $page = 'home';
            break;
        case 'changepassword':
            $data = ChangePassword();
            if($data['passwordvalid']){
                try{
                UpdatePassword($data['password']);
                $page = 'home';
                }
                catch(Exception $e){
                    $data['genericErr'] = 'sorry er is een technische storing2';
                }
            }
            break;
        case "webshop":
            // Optioneel kan je onderstaande code ook in een functie zetten $data = GetWebshopData();
            try {
                 $data['products'] = SearchForProducts();
            } 
            catch (Exception $e) {
                 $data['genericErr'] = "Kan de producten niet ophalen, probeer het later nogmaals";
                 LogDebug("Error collecting products: " . $e -> getMessage());
            }
            break;

        case "webshopitem":
            // Optioneel kan je onderstaande code ook in een functie zetten $data = GetWebshopItemData();
            try {
                $row = GetUrlVar("Row"); // de default is al "". Ik zou deze variabele "id" of "productId" noemen
                $data['product'] = SearchForProductById($row); // Maak een functie die de data voor 1 product of NULL teruggeeft
            } 
            catch (Exception $e) {
                 $data['genericErr'] = "Kan dit product niet ophalen, probeer het later nogmaals";
                 LogDebug("Error collecting product with id " . $row . ": " . $e -> getMessage());
            }
            break;
    }
    $data['page'] = $page;
    $data['menu'] = array('home' => 'Home', 'about' => 'About', 'contact' => 'Contact', 'webshop' => 'Webshop');
    if (isUserLogIn()) {
        $data['menu']['changepassword'] = "verander wachtwoord"; 
        $data['menu']['logout'] = "Logout " . getLogInUsername(); 
    } else {
        $data['menu']['register'] = "Register";
        $data['menu']['login'] = "Login";
    }
    return $data;
}

function GetRequestedPage(){
    $requested_type = $_SERVER['REQUEST_METHOD']; 
    if ($requested_type == 'POST'){
        $requested_page = GetPostVar('page','home'); 
    }else{
        $requested_page = GetUrlVar('page','home'); 
    } 
    return $requested_page; 
}

function ShowResponsePage($data){
    BeginDocument();
    ShowHeadSection();
    ShowBodySection($data);
    EndDocument();
}

function GetArrayVar($array, $key, $default=''){
    return isset($array[$key]) ? $array[$key] : $default;
}

function GetPostVar($key, $default=''){
    return GetArrayVar($_POST, $key, $default);
}

function GetUrlVar($key, $default=''){
    return GetArrayVar($_GET, $key, $default);
}

function BeginDocument(){
    echo '<!doctype html>
    <html>';
}

function ShowHeadSection(){
    echo '<head>
    <link rel="stylesheet" href="CSS/stylesheet.css">
    <title>About</title>
    </head>';
}

function ShowBodySection($data) { 
   echo '    <body>' . PHP_EOL; 
   ShowHeader($data);
   ShowMenu($data); 
   ShowGenericErr($data);
   ShowContent($data); 
   ShowFooter(); 
   echo '    </body>' . PHP_EOL; 
} 

function EndDocument(){
    echo '</html>';
}

function ShowHeader($data){
    switch ($data['page']){
        default:
            echo '<h1>gefaald</h1>';
            break;
        case 'home':
            Echo '<h1>Home</h1>';
            break;
        case 'about':
            Echo '<h1>About</h1>';
            break;
        case 'contact':
            Echo '<h1>Contact</h1>';
            break;
        case 'register':
            Echo '<h1>Register</h1>';
            break;
        case 'login':
            Echo '<h1>Login</h1>';
            break;
        case 'changepassword':
            Echo '<h1>Verander wachtwoord</h1>';
            break;
        case 'webshop':
            Echo '<h1>webshop</h1>';
            break;
        case 'webshopitem':
            Echo '<h1>details</h1>';
            break;
    }
}

function ShowMenu($data){
    echo '<ul class="menu">';
    foreach($data['menu'] as $link => $label) { showMenuItem($link,$label); }
    echo '</ul>';
}

function Showmenuitem($name, $message, $username = ''){
    echo'<li class="menuitem"><a href="index.php?page=';echo $name; echo'">';echo $message, $username; echo'</a></li>';
}

function ShowGenericErr($data){
    echo '<span class="error">' . $data['genericErr'] . '</span>';
}

function ShowContent($data){
    switch ($data['page']){
        default:
            echo '<a>error 404 pagina niet gevonden</a><br>
            <li class="menuitem"><a href="index.php?page=home">Terug gaan naar de homepagina</a></li>';
            break;
        case 'home':
            require('home.php');
            ShowHomeContent();
            break;
        case 'about':
            require('about.php');
            ShowAboutContent();
            break;
        case 'contact':
            require('contact.php');
            ShowContactContent();
            break;
        case 'register':
            ShowRegisterContent($data);
            break;
        case 'login':
            ShowLoginContent($data);
            break;
        case 'changepassword':
            ShowPasswordContent($data);
            break;
        case 'webshop':
            require('webshop.php');
            ShowWebshopContent();
            break;
        case 'webshopitem':
            require('webshopitem.php');
            ShowWebshopItemContent();
            break;
    }
}

function ShowWebshopContent(){
    $products = SearchForProducts();
    ShowWebshop($products);
};
function ShowWebshopItemContent(){
    $requested_item = GetUrlVar('row','');
    $products = SearchForProducts();
    ShowWebshopItem($products, $requested_item);
};

function ShowRegisterContent($data){
    if($data['registervalid'] == false){
        ShowRegisterForm($data);
    }
}

function ShowLoginContent($data){
    if($data['loginvalid'] == false){
        ShowLoginForm($data);
    }
}

function ShowPasswordContent($data){
    if($data['passwordvalid'] == false){
        ShowPasswordForm($data);
    }
}

function ShowContactContent(){
    $data = ValidateContact();
    if($data['valid'] == false){
        ShowFormContent($data);
    } else {
        ShowThanksContent($data);
    }
}

function ShowFooter(){
    echo '<footer>
    <p>Copyright &copy; 2023 Stijn Engelmoer</p>
    </footer>';
}
?>