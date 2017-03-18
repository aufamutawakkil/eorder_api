<?php 
/**
 * Main Router
 * 
 * tambah router untuk API disini
 * end point dibuat group untuk setiap object API nya supaya rapi
 * 
 * API Docs otomatis tergenerate dengan format berikut:
 * 
 * setArguments(Array)
 * 
 * endpoint = end point api access (contoh: /api/user/login)
 * params = array index -> nama variable parameter, array value -> keterangan (bisa html)
 * 
 */


use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// index utama tampilkan versi API
$app->get('/', function (Request $request, Response $response) {
    $newResponse = $response->withJson( array( 'app_name' => 'E-ORDER API', 'version' => '1.0' ) );
    return $newResponse;
})->setName('/')->setArguments(array('endpoint' => '/', 'params' => 'none'));

// untuk generate halaman API Documentation
$app->get('/docs', function (Request $request, Response $response) {
        $routes = RouteDumper::getAllRoutes($this);
        return $this->renderer->render($response, "docs.php", [
            'title' => 'BIBEX API Documentation',
            'routes' => $routes,
            'request' => $request
        ]);

})->setName('API Docs')->setArguments(array('endpoint' => '/docs', 'params' => 'none'));;

// ambil settingan aplikasi
$app->group('/settings', function () use ($app) {
    $app->get('/all', '\Controller\SettingController::getAll')
        ->setName('Get All Settings')
        ->setArguments(array('endpoint' => '/settings/all',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/{alias}', '\Controller\SettingController::getbyAlias')
        ->setName('Get Setting By Alias')
        ->setArguments(array('endpoint' => '/settings/{alias}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));
});


$app->group('/toko', function () use ($app) {
    $app->get('', '\Controller\TokoController::getAll')
        ->setName('Get All Toko')
        ->setArguments(array('endpoint' => '/toko',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));
});


$app->group('/kategori_barang', function () use ($app) {
    $app->get('/{id}', '\Controller\KategoriBarangController::getAll')
        ->setName('Get kategoi barang by id')
        ->setArguments(array('endpoint' => '/kategori_barang/{id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));
});


$app->group('/ojek', function () use ($app) {
	$app->post('/order/{user_id}', '\Controller\OjekController::save')
        ->setName('Order Ojek')
        ->setArguments(array('endpoint' => '/order/{user_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));	

        $app->get('/order/{order_id}', '\Controller\OjekController::getById')
        ->setName('Get Order Ojek by Id')
        ->setArguments(array('endpoint' => '/order/{order_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    )); 
});


$app->group('/barang', function () use ($app) {
    $app->get('/{id}', '\Controller\BarangController::getAll')
        ->setName('Get Barang by id menu')
        ->setArguments(array('endpoint' => '/barang/{id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->post('/order', '\Controller\BarangController::order')
        ->setName('Order Barang')
        ->setArguments(array('endpoint' => '/barang/order',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));


    $app->get('/history/{user_id}/{id}', '\Controller\BarangController::historyById')
        ->setName('Get history by id')
        ->setArguments(array('endpoint' => '/barang/history/{user_id}/{id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/history_status/{user_id}/{status}', '\Controller\BarangController::historyByStatus')
        ->setName('Get history by status')
        ->setArguments(array('endpoint' => '/history/{user_id}/{status}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));
});

$app->group('/history', function () use ($app) {
    $app->get('/user/{user_id}', '\Controller\HistoryController::getAllByUser')
        ->setName('Get history by user')
        ->setArguments(array('endpoint' => '/user/{user_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/user/{user_id}/status/{status_id}', '\Controller\HistoryController::getAllByStatus')
        ->setName('Get histoy by user and status')
        ->setArguments(array('endpoint' => '/user/{user_id}/status/{status_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/user/{user_id}/{id}', '\Controller\HistoryController::getById')
        ->setName('Get histoy by user and id')
        ->setArguments(array('endpoint' => '/user/{user_id}/status/{status_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));
});


/*token*/
$app->get('/csrf', function(Request $request, Response $response){
return json_encode(['status' => true,'data' => [ $request->getAttribute('csrf_name') => $request->getAttribute('csrf_value') ] ]  );
})
->setName('Get CSRF')
->setArguments(array('endpoint' => '/user/csrf', 
    'params' => 'none'
));


//vendor
$app->group('/vendor', function () use ($app) {
    // validasi login
    $app->post('/login', '\Controller\VendorController::login')
        ->setName('Vendor Login')
        ->setArguments(array('endpoint' => '/vendor/login', 
            'params' => "none"));

    $app->get('/order/newest/{type_vendor}', '\Controller\VendorController::newestOrder')
        ->setName('order terbaru')
        ->setArguments(array('endpoint' => '/vendor/order/newest', 
            'params' => "none"));


    $app->get('/order/history/{vendor_id}/status/{status_id}', '\Controller\VendorController::history')
        ->setName('order histiry')
        ->setArguments(array('endpoint' => '/vendor/order/history/{vendor_id}/{status_id}', 
            'params' => "none"));

    $app->post('/order/accept', '\Controller\VendorController::accept')
        ->setName('accepting order')
        ->setArguments(array('endpoint' => '/vendor/order/newest', 
            'params' => "none"));

     $app->get('/{vendor_id}', '\Controller\VendorController::getById')
        ->setName('accepting order')
        ->setArguments(array('endpoint' => '/vendor/{vendor_id}', 
            'params' => "none"));

     $app->post('/cancel_order/{user_id}', '\Controller\VendorController::cancelOrder')
        ->setName('Cancel booking')
        ->setArguments(array('endpoint' => '/vendor/cancel_booking/{user_id}', 
            'params' => "none"));
            
    $app->post('/finish_order', '\Controller\VendorController::finishOrder')
        ->setName('Finish booking')
        ->setArguments(array('endpoint' => '/vendor/finish_order', 
            'params' => "none"));
            
    $app->post('/update_position', '\Controller\VendorController::updateLastPosition')
        ->setName('Finish booking')
        ->setArguments(array('endpoint' => '/vendor/update_position', 
            'params' => "none"));
    
    $app->get('/get_position/{type_vendor}', '\Controller\VendorController::getVendorPosition')
        ->setName('Finish booking')
        ->setArguments(array('endpoint' => '/vendor/get_position/{type_vendor}', 
            'params' => "none"));
});


$app->group('/credit', function () use ($app) {
    $app->post('/input_code', '\Controller\CreditController::inputCode')
        ->setName('Input Code')
        ->setArguments(array('endpoint' => '/credit/input_code', 
            'params' => array(
                'phone' => 'phone',
    )));
});


// api user
$app->group('/user', function () use ($app) {
    //snyc setting tarif
    $app->get('/sync_setting', '\Controller\UserController::syncSetting')
        ->setName('User Login')
        ->setArguments(array('endpoint' => '/user/sync_tarif', 
            'params' => array(
                'phone' => 'phone',
    )));
    
    // validasi login
    $app->post('/login', '\Controller\UserController::login')
        ->setName('User Login')
        ->setArguments(array('endpoint' => '/user/login', 
            'params' => array(
                'phone' => 'phone',
    )));

    // ambil data user dari ID
    $app->get('/{id}', '\Controller\UserController::getbyId')
        ->setName('Get User by ID')
        ->setArguments(array('endpoint' => '/user/{id}',
    ));
    
    $app->post('/refresh_token', '\Controller\UserController::refreshToken')
        ->setName('Refresh Token')
        ->setArguments(array('endpoint' => '/user/refresh_token',
    ));
    
    $app->post('/rate_vendor', '\Controller\UserController::rateVendor')
        ->setName('Rating Vendor')
        ->setArguments(array('endpoint' => '/user/rate_vendor',
    ));

    // registrasi user
    $app->post('/register', '\Controller\UserController::save')
        ->setName('User Registration')
        ->setArguments(array('endpoint' => '/user/register', 
            'params' => array(
                'phone' => 'phone',
                'email'=> 'email',
                'name' => 'name',
            )
    ));
    
    //change number
    $app->post('/change_number', '\Controller\UserController::changeNumber')
        ->setName('User Registration')
        ->setArguments(array('endpoint' => '/user/change_number', 
            'params' => array(
                'phone' => 'phone',
                'email'=> 'email',
                'name' => 'name',
            )
    ));
    
     // rating vendor
    $app->post('/give_rating', '\Controller\UserController::giveRating')
        ->setName('Merating vendor')
        ->setArguments(array('endpoint' => '/user/give_rating', 
            'params' => 'none'
    ));
});


$app->group('/saldo', function () use ($app) {
     $app->post('/topup', '\Controller\SaldoController::topup')
        ->setName('Topup Saldo')
        ->setArguments(array('endpoint' => '/saldo/topup', 
            'params' => []));
});


//api faskes
$app->group('/faskes', function () use ($app) {
     $app->get('', '\Controller\FaskesController::getAll')
        ->setName('Get Faskes')
        ->setArguments(array('endpoint' => '/faskes', 
            'params' => 'none' ));
});



// api blog

$app->group('/blog', function () use ($app) {
     $app->get('', '\Controller\BlogController::getAll')
        ->setName('Get All Blog')
        ->setArguments(array('endpoint' => '/blog', 
            'params' => 'none'));

     $app->post('', '\Controller\BlogController::save')
        ->setName('Insert Blog')
        ->setArguments(array('endpoint' => '/blog', 
            'params' => array(
                'content'=> 'content',
                'attach' => 'attach',
                'comment_count' => 'comment_count',
                'faskes_id' => 'faskes_id',
                'klinik_id' => 'klinik_id',
                'user_id' => 'user_id',
        )));

    $app->get('delete\{id}', '\Controller\BlogController::delete')
        ->setName('Delete Blog')
        ->setArguments(array('endpoint' => '/blog/delete/{id}', 
            'params' => 'none'));

    $app->post('/{id}/comment', '\Controller\BlogController::saveComment')
        ->setName('Get Blog Comment')
        ->setArguments(array('endpoint' => '/blog/{id}/comment', 
            'params' => [
                'user_id',
                'content',
            ]));

     $app->post('/{id}/comment/{blog_id}', '\Controller\BlogController::deleteComment')
        ->setName('Get Blog Comment')
        ->setArguments(array('endpoint' => '/blog/{id}/comment/{blog_id}', 
            'params' => 
            'none'));
});



$app->group('/address',function () use ($app){
    $app->get('', '\Controller\AddressController::getAll')
        ->setName('Get Address')
        ->setArguments(array('endpoint' => '/address', 
            'params' => 'none'));
});


$app->group('/klinik/{faskes_id}',function () use ($app){
    $app->get('', '\Controller\KlinikController::getAll')
        ->setName('Get Klinik')
        ->setArguments(array('endpoint' => '/klinik/{faskes_id}', 
            'params' => 'none'));
});



$app->group('/aktifasi',function () use ($app){
    $app->post('/activate', '\Controller\AktifasiController::activate')
        ->setName('Get Klinik')
        ->setArguments(array('endpoint' => '/aktifasi/activate', 
            'params' => ['activation_code' => 'Kode Aktivasi']));

    $app->post('/reactivation', '\Controller\AktifasiController::reactivation')
        ->setName('Reactivation Activation Code')
        ->setArguments(array('endpoint' => '/aktifasi/reactivation', 
            'params' => 
            [
                'user_id' => 'User ID',
                'phone'  => 'phone'
            ])
        );
});





/* modification end here */