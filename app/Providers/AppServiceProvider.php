<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Event;
use Slides\Saml2\Events\SignedIn;
use Slides\Saml2\Events\SignedOut;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ( env('APP_ENV') !== 'local' ) URL::forceScheme('https');
        \Carbon\Carbon::setLocale(config('app.locale'));

        Event::listen(\Slides\Saml2\Events\SignedIn::class, function (\Slides\Saml2\Events\SignedIn $event) {
            $messageId = $event->getAuth()->getLastMessageId();

            // your own code preventing reuse of a $messageId to stop replay attacks
            $samlUser = $event->getSaml2User();

            $userData = [
                'id' => $samlUser->getUserId(),
                'attributes' => $samlUser->getAttributes(),
                'assertion' => $samlUser->getRawSamlAssertion()
            ];

            dd($samlUser);

            //$user = // find user by ID or attribute

            // Login a user.
            //Auth::login($user);
        });



    }
}
