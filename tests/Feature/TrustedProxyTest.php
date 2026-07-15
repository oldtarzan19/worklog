<?php

use Illuminate\Support\Facades\Route;

test('it generates secure asset URLs behind a trusted proxy', function () {
    Route::get('/_test/trusted-proxy', fn (): string => asset('build/assets/app.js'));

    $response = $this->withServerVariables([
        'REMOTE_ADDR' => '10.0.0.2',
        'HTTP_HOST' => 'worklog.pixelcode.hu',
        'SERVER_PORT' => '80',
        'HTTP_X_FORWARDED_PROTO' => 'https',
        'HTTP_X_FORWARDED_HOST' => 'worklog.pixelcode.hu',
        'HTTP_X_FORWARDED_PORT' => '443',
    ])->get('http://worklog.pixelcode.hu/_test/trusted-proxy');

    $response
        ->assertSuccessful()
        ->assertSeeText('https://worklog.pixelcode.hu/build/assets/app.js');
});
