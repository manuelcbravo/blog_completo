<?php

test('la portada pública abre sin sesión', function () {
    $this->get(route('home'))->assertOk();
});

test('el panel sigue exigiendo sesión', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
