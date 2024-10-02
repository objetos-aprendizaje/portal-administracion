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
                        <label for="google_client_id">ID Cliente <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['google_client_id'] }}" placeholder=".apps.googleusercontent.com"
                            class="poa-input" type="text" id="google_client_id" name="google_client_id" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="google_client_secret">Clave secreta de cliente <span
                                class="text-danger">*</span></label>
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
                        <label for="facebook_client_id">ID Cliente <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['facebook_client_id'] }}" placeholder=".apps.facebook.com"
                            class="poa-input" type="text" id="facebook_client_id" name="facebook_client_id" />
                    </div>
                </div>


                <div class="field">
                    <div class="label-container label-center">
                        <label for="linkedin_url">Clave secreta de cliente <span class="text-danger">*</span></label>
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
                                <input {{ $general_options['twitter_login_active'] ? 'checked' : '' }} type="checkbox"
                                    id="twitter_login_active" name="twitter_login_active" class="sr-only peer">
                                <div
                                    class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="twitter_client_id">ID Cliente <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['twitter_client_id'] }}" placeholder=".apps.twitter.com"
                            class="poa-input" type="text" id="twitter_client_id" name="twitter_client_id" />
                    </div>
                </div>


                <div class="field">
                    <div class="label-container label-center">
                        <label for="twitter_client_secret">Clave secreta de cliente <span
                                class="text-danger">*</span></label>
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
                                <input {{ $general_options['linkedin_login_active'] ? 'checked' : '' }} type="checkbox"
                                    id="linkedin_login_active" name="linkedin_login_active" class="sr-only peer">
                                <div
                                    class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="linkedin_client_id">ID Cliente <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['linkedin_client_id'] }}" placeholder=".apps.linkedin.com"
                            class="poa-input" type="text" id="linkedin_client_id" name="linkedin_client_id" />
                    </div>
                </div>


                <div class="field">
                    <div class="label-container label-center">
                        <label for="linkedin_client_secret">Clave secreta de cliente <span
                                class="text-danger">*</span></label>
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

    <div class="poa-container mb-8">

        <h2>CAS</h2>

        <form id="cas-login-form">
            @csrf

            <div class="poa-form">

                <div class="field">
                    <div class="label-container label-center">
                        <label for="cas_login_active">Activado</label>
                    </div>

                    <div class="content-container little">
                        <div class="checkbox">
                            <label for="cas_login_active" class="inline-flex relative items-center cursor-pointer">
                                <input {{ $cas_active ? 'checked' : '' }} type="checkbox" id="cas_login_active"
                                    name="cas_login_active" class="sr-only peer">
                                <div
                                    class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                                </div>
                            </label>
                        </div>

                        <a class="{{ $urlCasMetadata ? 'block' : 'hidden' }}" id="view-metadata-cas"
                            href="{{ $urlCasMetadata }}" target="_blank">Ver metadata</a>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="cas_entity_id">Entity ID <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container little">
                        <input maxlength="255" value="{{ $cas ? $cas['idp_entity_id'] : '' }}" placeholder="Entity ID"
                            class="poa-input" type="text" id="cas_entity_id" name="cas_entity_id" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="cas_login_url">Login URL <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container little">
                        <input maxlength="255" value="{{ $cas ? $cas['idp_login_url'] : '' }}" placeholder="Login URL"
                            class="poa-input" type="text" id="cas_login_url" name="cas_login_url" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="cas_logout_url">Logout URL <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container little">
                        <input maxlength="255" value="{{ $cas ? $cas['idp_logout_url'] : '' }}" placeholder="Logout URL"
                            class="poa-input" type="text" id="cas_logout_url" name="cas_logout_url" />
                    </div>
                </div>


                <div class="field">
                    <div class="label-container label-center">
                        <label for="cas_certificate">Certificado x509 <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $cas ? $cas['idp_x509_cert'] : '' }}" class="poa-input" type="text"
                            id="cas_certificate" name="cas_certificate" />
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="save-rrss-btn">Guardar
                {{ e_heroicon('paper-airplane', 'outline') }}</button>

        </form>


    </div>

    <div class="poa-container mb-8">

        <h2>Rediris</h2>

        <form id="rediris-login-form">
            @csrf

            <div class="poa-form">

                <div class="field">
                    <div class="label-container label-center">
                        <label for="rediris_login_active">Activado</label>
                    </div>

                    <div class="content-container little">
                        <div class="checkbox">
                            <label for="rediris_login_active" class="inline-flex relative items-center cursor-pointer">
                                <input {{ $rediris_active ? 'checked' : '' }} type="checkbox" id="rediris_login_active"
                                    name="rediris_login_active" class="sr-only peer">
                                <div
                                    class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                                </div>
                            </label>
                        </div>

                        <a class="{{ $urlRedirisMetadata ? 'block' : 'hidden' }}" id="view-metadata-rediris"
                            href="{{ $urlRedirisMetadata }}" target="_blank">Ver metadata</a>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="rediris_entity_id">Entity ID <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container little">
                        <input maxlength="255" value="{{ $rediris ? $rediris['idp_entity_id'] : '' }}"
                            placeholder="Entity ID" class="poa-input" type="text" id="rediris_entity_id"
                            name="rediris_entity_id" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="rediris_login_url">Login URL <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container little">
                        <input maxlength="255" value="{{ $rediris ? $rediris['idp_login_url'] : '' }}"
                            placeholder="Login URL" class="poa-input" type="text" id="rediris_login_url"
                            name="rediris_login_url" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="rediris_logout_url">Logout URL <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container little">
                        <input maxlength="255" value="{{ $rediris ? $rediris['idp_logout_url'] : '' }}"
                            placeholder="Logout URL" class="poa-input" type="text" id="rediris_logout_url"
                            name="rediris_logout_url" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="rediris_certificate">Certificado x509 <span class="text-danger">*</span></label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $rediris ? $rediris['idp_x509_cert'] : '' }}" class="poa-input" type="text"
                            id="rediris_certificate" name="rediris_certificate" />
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="save-rrss-btn">Guardar
                {{ e_heroicon('paper-airplane', 'outline') }}</button>

        </form>


    </div>
@endsection
