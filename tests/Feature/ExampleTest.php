<?php
namespace Tests\Feature;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_route_slash_redirects_to_dashboard(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/dashboard');
    }

    public function test_dashboard_requires_auth(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirectToRoute('login');
    }
}
