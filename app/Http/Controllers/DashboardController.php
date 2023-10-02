<?php

namespace App\Http\Controllers;

use App\Services\SallaAuthService;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

use SallaSDK;
use GuzzleHttp\Client;

class DashboardController extends Controller
{
    /**
     * @var SallaAuthService
     */
    private $salla;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(SallaAuthService $salla)
    {
        $this->middleware('auth');
        $this->salla = $salla;
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable|\Illuminate\Http\RedirectResponse
     * @throws IdentityProviderException
     */
    public function __invoke()
    {
        $products = [];
        $store = null;

        if (auth()->user()->token) {
            // set the access token to our service
            // you can load the user profile from your database in your app
            $this->salla->forUser(auth()->user());

            // you need always to check the token before made a request
            // If the token expired, lets request a new one and save it to the database
            try {
                $this->salla->getNewAccessToken();
            } catch (IdentityProviderException $exception) {
                // in case the token access token & refresh token is expired
                // lets redirect the user again to Salla authorization service to get a new token
                return redirect()->route('oauth.redirect');
            }

            // let's get the store details to show it
            $store = $this->salla->getStoreDetail();


            // Fetching products list using Salla SDK
            $config = SallaSDK\Configuration::getDefaultConfiguration()->setAccessToken($this->salla->getNewAccessToken());
            $apiInstance = new SallaSDK\Api\ProductsApi(new Client(), $config);

            try {
                $products = $apiInstance->getProducts()->getData();
            } catch (Exception $e) {
                echo 'Exception when calling ProductsApi->getProducts: ', $e->getMessage(), PHP_EOL;
            }
        }

        return view('dashboard', [
            'products' => array_slice($products, 0, min(5, count($products))),
            'store'    => $store
        ]);
    }
}
