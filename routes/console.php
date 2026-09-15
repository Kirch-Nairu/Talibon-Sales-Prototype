<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('about:prototype', function (): void {
    $this->info('Talibon Intra-Office Portal - municipal workflow prototype');
});
