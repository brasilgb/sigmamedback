<?php

test('returns a successful response', function () {
    $this->withoutVite();

    $response = $this->get(route('home'));

    $response
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('welcome'));
});

test('privacy policy returns a successful response', function () {
    $this->withoutVite();

    $response = $this->get(route('privacy-policy'));

    $response
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('privacy-policy'));
});
