<?php

namespace Tests\Feature;

use App\Lib\SocialLogin;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use ReflectionClass;
use Tests\TestCase;

class GoogleSocialLoginTest extends TestCase
{
    public function test_google_callback_accepts_get_and_form_post_responses(): void
    {
        $route = app('router')->getRoutes()->getByName('user.social.login.callback');

        $this->assertNotNull($route);
        $this->assertContains('GET', $route->methods());
        $this->assertContains('POST', $route->methods());
    }

    public function test_google_uses_form_post_response_mode_in_production(): void
    {
        $originalEnvironment = $this->app['env'];
        $this->app['env'] = 'production';

        try {
            $driver = Mockery::mock();
            $driver->shouldReceive('with')
                ->once()
                ->with(['response_mode' => 'form_post'])
                ->andReturnSelf();
            $driver->shouldReceive('redirect')->once()->andReturn('google-redirect');

            Socialite::shouldReceive('driver')->once()->with('google')->andReturn($driver);

            $reflection = new ReflectionClass(SocialLogin::class);
            $socialLogin = $reflection->newInstanceWithoutConstructor();
            $provider = $reflection->getProperty('provider');
            $provider->setValue($socialLogin, 'google');

            $this->assertSame('google-redirect', $socialLogin->redirectDriver());
        } finally {
            $this->app['env'] = $originalEnvironment;
        }
    }
}
