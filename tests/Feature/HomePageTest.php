<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_home_page_renders_the_vue_mount_point(): void
    {
        $this->withoutVite();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('app');
        $response->assertSee('id="app"', false);
        $response->assertSee('data-app-name="MyCar"', false);
    }
}
