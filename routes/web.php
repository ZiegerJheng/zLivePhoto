<?php

use Facebook\Facebook;
use Facebook\PersistentData\PersistentDataInterface;

class MyLaravelPersistentDataHandler implements PersistentDataInterface
{
    protected $sessionPrefix = 'FBRLH_';

    public function get($key)
    {
        return session($key);
    }

    public function set($key, $value)
    {
        session([$key => $value]);
    }
}

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

Route::get('/', function () {
    return redirect()->route('3rd-fb-login');
});

Route::get('/3rd/facebook/login', function () {
    $fb = new Facebook([
        'app_id' => config('facebook.app_id'),
        'app_secret' => config('facebook.app_secret'),
        'persistent_data_handler' => new MyLaravelPersistentDataHandler()
    ]);

    $helper = $fb->getRedirectLoginHelper();
    $permissions = ['user_managed_groups'];
    $loginUrl = $helper->getLoginUrl(config('app.url') . '/3rd/facebook/login-callback', $permissions);

    return Redirect::away($loginUrl);
})->name('3rd-fb-login');

Route::get('/3rd/facebook/login-callback', function () {
    $fb = new Facebook([
        'app_id' => config('facebook.app_id'),
        'app_secret' => config('facebook.app_secret'),
        'persistent_data_handler' => new MyLaravelPersistentDataHandler()
    ]);

    $helper = $fb->getRedirectLoginHelper();

    try {
        $accessToken = $helper->getAccessToken();
    } catch(Facebook\Exceptions\FacebookResponseException $e) {
        dd($e->getMessage());
    } catch(Facebook\Exceptions\FacebookSDKException $e) {
        dd($e->getMessage());
    }

    if(false == $accessToken->isLongLived()) {
        $oAuth2Client = $fb->getOAuth2Client();
        $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
    }

    session(['fb_user_access_token' => (string) $accessToken]);

    return redirect()->route('slides');
});

Route::get('/slides', function () {
    return view('slides');
})->name('slides');

Route::get('/photoFeeds', function() {
    $fbGroupPhotos = getPhotoFeeds();
    return response()->json([ 'feeds' => $fbGroupPhotos ]);
});

function getPhotoFeeds()
{
    $fb = new Facebook([
        'app_id' => config('facebook.app_id'),
        'app_secret' => config('facebook.app_secret'),
        'persistent_data_handler' => new MyLaravelPersistentDataHandler()
    ]);

    $fb->setDefaultAccessToken(session('fb_user_access_token'));

    $response = $fb->get('/885989284866779/feed?fields=from,message,object_id,type&limit=50');
    $groupFeeds = $response->getDecodedBody();
    
    $fbGroupPhotos = [];
    while(count($groupFeeds['data']) > 0) {
        $allPhotosObjectIds = [];

        foreach($groupFeeds['data'] as $feed ){
            if('photo' != $feed['type']){
                continue;
            }

            //$response = $fb->get($feed['object_id'] . '?fields=images');
            //$imageData = $response->getDecodedBody();

            $fbGroupPhotos[$feed['object_id']] = [
                'id' => $feed['object_id'],
                'userName' => $feed['from']['name'],
                'message' => (array_key_exists('message', $feed)) ? $feed['message'] : ''
                //'photoUrl' => $imageData['images'][0]['source']
            ];

            $allPhotosObjectIds[] = $feed['object_id'];
        }

        $allPhotosObjectIdStr = implode(',', $allPhotosObjectIds);
        $response = $fb->get('?ids=' . $allPhotosObjectIdStr . '&fields=images');
        $imageData = $response->getDecodedBody();
        //dd($imageData);

        foreach($allPhotosObjectIds as $allPhotosObjectId){
            $fbGroupPhotos[$allPhotosObjectId]['photoUrl'] = $imageData[$allPhotosObjectId]['images'][0]['source'];
        }
        //dd($fbGroupPhotos);

        $next = parse_url($groupFeeds['paging']['next']);
        $nextQuery = '/885989284866779/feed?' . $next['query'];

        $response = $fb->get($nextQuery);
        $groupFeeds = $response->getDecodedBody();
    }

    $finalFBGroupPhotos = [];
    foreach($fbGroupPhotos as $fbGroupPhoto){
        $finalFBGroupPhotos[] = $fbGroupPhoto;
    }

    return $finalFBGroupPhotos;
}
