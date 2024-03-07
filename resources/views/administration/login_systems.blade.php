@extends('layouts.app')
@section('content')
    <div class="poa-container mb-8">

        <h2>Google</h2>

        <form id="google-login-form">
            @csrf

            <div class="poa-form">

                <div class="field">
                    <div class="label-container label-center">
                        <label for="facebook_url">Activado</label>
                    </div>

                    <div class="content-container little">
                        <div class="checkbox">
                            <label for="google_login_active" class="inline-flex relative items-center cursor-pointer">
                                <input {{ $general_options['google_login_active'] ? 'checked' : '' }} type="checkbox"
                                    id="google_login_active" name="google_login_active" class="sr-only peer">
                                <div
                                    class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="google_client_id">ID Cliente</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['google_client_id'] }}" placeholder=".apps.googleusercontent.com"
                            class="poa-input" type="text" id="google_client_id" name="google_client_id" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="google_client_secret">Clave secreta de cliente</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['google_client_secret'] }}" class="poa-input" type="text"
                            id="google_client_secret" name="google_client_secret" />
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="save-rrss-btn">Guardar
                {{ e_heroicon('paper-airplane', 'outline') }}</button>

        </form>

    </div>

    <div class="poa-container mb-8">

        <h2>Facebook</h2>

        <form id="facebook-login-form">
            @csrf

            <div class="poa-form">

                <div class="field">
                    <div class="label-container label-center">
                        <label for="facebook_login_active">Activado</label>
                    </div>

                    <div class="content-container little">
                        <div class="checkbox">
                            <label for="facebook_login_active" class="inline-flex relative items-center cursor-pointer">
                                <input {{ $general_options['facebook_login_active'] ? 'checked' : '' }} type="checkbox"
                                    id="facebook_login_active" name="facebook_login_active" class="sr-only peer">
                                <div
                                    class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="facebook_client_id">ID Cliente</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['facebook_client_id'] }}"
                            placeholder=".apps.facebook.com" class="poa-input" type="text"
                            id="facebook_client_id" name="facebook_client_id" />
                    </div>
                </div>


                <div class="field">
                    <div class="label-container label-center">
                        <label for="linkedin_url">Clave secreta de cliente</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['facebook_client_secret'] }}" class="poa-input" type="text"
                            id="facebook_client_secret" name="facebook_client_secret" />
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="save-rrss-btn">Guardar
                {{ e_heroicon('paper-airplane', 'outline') }}</button>

        </form>

    </div>

    <div class="poa-container mb-8">

        <h2>Twitter</h2>

        <form id="twitter-login-form">
            @csrf

            <div class="poa-form">

                <div class="field">
                    <div class="label-container label-center">
                        <label for="twitter_login_active">Activado</label>
                    </div>

                    <div class="content-container little">
                        <div class="checkbox">
                            <label for="twitter_login_active" class="inline-flex relative items-center cursor-pointer">
                                <input {{ $general_options['twitter_login_active'] ? 'checked' : '' }}
                                    type="checkbox" id="twitter_login_active" name="twitter_login_active" class="sr-only peer">
                                <div
                                    class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="twitter_client_id">ID Cliente</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['twitter_client_id'] }}" placeholder=".apps.twitter.com"
                            class="poa-input" type="text" id="twitter_client_id" name="twitter_client_id" />
                    </div>
                </div>


                <div class="field">
                    <div class="label-container label-center">
                        <label for="twitter_client_secret">Clave secreta de cliente</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['twitter_client_secret'] }}" class="poa-input" type="text"
                            id="twitter_client_secret" name="twitter_client_secret" />
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="save-rrss-btn">Guardar
                {{ e_heroicon('paper-airplane', 'outline') }}</button>

        </form>


    </div>


    <div class="poa-container mb-8">

        <h2>Linkedin</h2>

        <form id="linkedin-login-form">
            @csrf

            <div class="poa-form">

                <div class="field">
                    <div class="label-container label-center">
                        <label for="linkedin_login_active">Activado</label>
                    </div>

                    <div class="content-container little">
                        <div class="checkbox">
                            <label for="linkedin_login_active" class="inline-flex relative items-center cursor-pointer">
                                <input {{ $general_options['linkedin_login_active'] ? 'checked' : '' }}
                                    type="checkbox" id="linkedin_login_active" name="linkedin_login_active" class="sr-only peer">
                                <div
                                    class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="linkedin_client_id">ID Cliente</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['linkedin_client_id'] }}" placeholder=".apps.linkedin.com"
                            class="poa-input" type="text" id="linkedin_client_id" name="linkedin_client_id" />
                    </div>
                </div>


                <div class="field">
                    <div class="label-container label-center">
                        <label for="linkedin_client_secret">Clave secreta de cliente</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['linkedin_client_secret'] }}" class="poa-input" type="text"
                            id="linkedin_client_secret" name="linkedin_client_secret" />
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="save-rrss-btn">Guardar
                {{ e_heroicon('paper-airplane', 'outline') }}</button>

        </form>


    </div>

@endsection
