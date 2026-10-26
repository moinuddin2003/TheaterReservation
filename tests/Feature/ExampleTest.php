<?php

it('shows the public customer home page to visitors', function () {
    $this->seed();

    $this->get('/')
        ->assertOk()
        ->assertSee('The Last Horizon');
});
