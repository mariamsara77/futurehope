<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_homepage_returns_successfully(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
    }
}
