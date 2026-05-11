<?php

test('application uses Sao Paulo timezone for generated dates', function () {
    expect(config('app.timezone'))->toBe('America/Sao_Paulo')
        ->and(now()->timezoneName)->toBe('America/Sao_Paulo');
});
