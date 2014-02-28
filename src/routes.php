<?

Route::get('piximage/{mode}/{size}/{patch}', function ($mode, $size, $patch) {
    return app()['piximage']->stream($mode, $size, $patch);
})->where('patch', '(.*)');