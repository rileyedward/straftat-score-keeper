<?php

it('redirects unauthenticated users to login', function () {
    $this->get('/')->assertRedirect('/login');
});

it('lets users in with correct passcode', function () {
    config()->set('app.passcode', 'secret-pw');

    $this->post('/login', ['passcode' => 'secret-pw'])->assertRedirect('/');
    expect(session('passcode_ok'))->toBeTrue();
});

it('rejects wrong passcode', function () {
    config()->set('app.passcode', 'secret-pw');

    $this->post('/login', ['passcode' => 'nope'])->assertSessionHasErrors('passcode');
    expect(session('passcode_ok'))->toBeNull();
});

it('logs out and clears session', function () {
    $this->withSession(['passcode_ok' => true])
        ->post('/logout')
        ->assertRedirect('/login');
});
