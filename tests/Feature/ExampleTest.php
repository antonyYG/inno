<?php

test('la raíz redirige al área autenticada', function () {
    $response = $this->get('/');

    // '/' redirige a /admin y, sin sesión iniciada, al login.
    $response->assertRedirect();
});
