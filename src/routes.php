<?

Route::get('piximage/{mode}/{size}/{patch}', function ($mode, $size, $patch) {
    return app()['piximage']->stream($mode, $size, $patch);
})->where('patch', '(.*)');

Route::get('piximage/wm/{hash}', function ($originalHash) {
    $hash  = pathinfo($originalHash, PATHINFO_FILENAME);
    $image = base64_decode($hash);
    return app()['piximage']->streamWatermark($image, $originalHash);
})->where('patch', '(.*)');