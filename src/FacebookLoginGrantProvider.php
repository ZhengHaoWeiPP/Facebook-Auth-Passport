<?php

namespace Panelplace\FacebookAuthPassport;

use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\Bridge\UserRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\PassportServiceProvider;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\PasswordGrant;
use Carbon\Carbon;
/**
 * Class FacebookLoginGrantProvider
 *
 * @package App\Providers
 */
class FacebookLoginGrantProvider extends PassportServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        
        Passport::tokensExpireIn(Carbon::now()->addDays(env('TOKEN_EXPIRY_IN')));

        Passport::refreshTokensExpireIn(Carbon::now()->addDays(env('REFRESH_TOKEN_EXPIRY_IN')));
        
        if (file_exists(__DIR__ . '/../../../../storage/oauth-private.key')) {
            app(AuthorizationServer::class)->enableGrantType($this->makeRequestGrant(), Passport::tokensExpireIn());
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Create and configure a Password grant instance.
     *
     * @return PasswordGrant
     */
    protected function makeRequestGrant()
    {
        $grant = new FacebookLoginRequestGrant(
            $this->app->make(UserRepository::class),
            $this->app->make(RefreshTokenRepository::class)
        );

        $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());

        return $grant;
    }
}
