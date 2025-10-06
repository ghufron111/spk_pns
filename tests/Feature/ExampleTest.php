<?php

test('root redirects to login', function () {
    $response = $this->get('/');
    $response->assertRedirect('/login');
});

test('login page loads', function(){
    $response = $this->get('/login');
    $response->assertStatus(200);
});
