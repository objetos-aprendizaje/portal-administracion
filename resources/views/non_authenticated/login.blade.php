@extends('non_authenticated.app')
@section('content')
    <section class="md:flex hidden">

        <div class="w-1/2">
            <img id="image-background" src="{{ asset('data/images/background_login.png') }}"
                class="object-cover w-full h-screen">
        </div>

        <div class="w-1/2 justify-center flex items-center">
            <div class="w-[530px] mb-[25px]">
                <div class="rounded-[20px] border py-[20px] px-[40px]">

                    @if ($general_options['poa_logo_1'])
                        <img class="mx-auto block max-w-[211px] max-h-[80px] mb-[15px]"
                            src="{{ $general_options['poa_logo_1'] }}" />
                    @endif

                    <div class="text-[28px] font-bold text-center mb-[15px]">Inicia sesión</div>

                    @if (session('sent_email_recover_password'))
                        <div class="bg-[#E7ECF3] py-[12px] px-[27px] rounded-[8px] mb-[15px] text-center">
                            <p>Se ha enviado un link para reestablecer la contraseña</p>
                            <p>
                                ¿No has recibido nada? <a href="javascript:void(0)"
                                    data-email-account="{{ session('email') }}"
                                    class="text-color_1 resend-email-confirmation">Reenviar email</a>
                            </p>
                        </div>
                    @endif

                    @if (session('link_recover_password_expired'))
                        <div class="bg-[#E7ECF3] py-[12px] px-[27px] rounded-[8px] mb-[15px] text-center">
                            <p>El link de reestablecimiento de contraseña ha expirado</p>
                            <p>
                                <a href="javascript:void(0)" data-email-account="{{ session('email') }}"
                                    class="text-color_1 resend-email-confirmation">Reenviar email</a>
                            </p>
                        </div>
                    @endif

                    <form id="loginFormDesktop" action="/login/authenticate" method="POST">
                        @csrf
                        <div class="mb-[25px]">
                            <div class="flex flex-col mb-[20px]">
                                <label class="px-3 mb-[8px]">Correo</label>
                                <input
                                    class="border-[1.5px] border-solid border-primary rounded-full p-3 focus:border-primary h-[60px]"
                                    type="text" name="email" value="" />
                            </div>

                            <div class="flex flex-col mb-[8px]">
                                <label class="px-3 mb-[8px]">Contraseña</label>
                                <input class="border-[1.5px] border-solid border-primary rounded-full h-[60px] p-3"
                                    name="password" type="password" />
                            </div>

                            <a href="{{ route('recover-password') }}" id="recover-password"
                                class="text-primary block px-3 mb-[20px] text-[16px]">¿Olvidaste la
                                contraseña?</a>


                            <button class="btn bg-primary text-white hover:bg-secondary w-full h-[60px]">Iniciar sesión
                                {{ e_heroicon('arrow-up-right', 'outline') }}</button>

                        </div>

                        @if (
                            $urlCas ||
                                $urlRediris ||
                                $parameters_login_systems['facebook_login_active'] ||
                                $parameters_login_systems['twitter_login_active'] ||
                                $parameters_login_systems['linkedin_login_active'] ||
                                $parameters_login_systems['google_login_active']
                        )
                            <div class="flex items-center justify-center space-x-2 mb-[25px]">
                                <div class="border-t w-full"></div>
                                <div>O</div>
                                <div class="border-t w-full"></div>
                            </div>
                        @endif


                        @if ($urlCas)
                            <a href="{{ $urlCas }}">
                                <div class="flex justify-center mb-[25px]">
                                    <div
                                        class="inline-flex border rounded-full hover:border-primary items-center justify-center pl-[6px] pr-[14px] py-[6px] gap-2">
                                        <div>
                                            <img src="{{ asset('/data/images/logo_min_boton_login.png') }}"
                                                class="w-[40px] h-[40px] mx-auto rounded-full  block" />
                                        </div>

                                        <div class="border-l h-10"></div>

                                        <div class="cursor-pointer">
                                            <p class="font-roboto-bold text-black">ACCESO UNIVERSIDAD</p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @endif


                        <div class="flex justify-center gap-[32px]">
                            @if ($parameters_login_systems)
                                @if ($parameters_login_systems['facebook_login_active'])
                                    <button type="button"
                                        class="border hover:border-primary flex items-center justify-center rounded-full w-[64px] h-[64px]">
                                        <a href="/auth/facebook">
                                            <img class="w-[45px] h-[45px]" src="data/images/login_icons/facebook.png" />
                                        </a>
                                    </button>
                                @endif

                                @if ($parameters_login_systems['twitter_login_active'])
                                    <button type="button"
                                        class="border hover:border-primary flex items-center justify-center rounded-full w-[64px] h-[64px]">
                                        <a href="/auth/twitter"><img class="w-[32px] h-[32px]"
                                                src="data/images/login_icons/x_icon.png" /></a>
                                    </button>
                                @endif

                                @if ($parameters_login_systems['linkedin_login_active'])
                                    <button type="button"
                                        class="border hover:border-primary flex items-center justify-center rounded-full w-[64px] h-[64px]">
                                        <a href="/auth/linkedin-openid"><img class="w-[32px] h-[32px]"
                                                src="data/images/login_icons/linkedin_icon.png" /></a>
                                    </button>
                                @endif

                                @if ($parameters_login_systems['google_login_active'])
                                    <button type="button"
                                        class="border hover:border-primary flex items-center justify-center rounded-full w-[64px] h-[64px]">
                                        <a href="/auth/google"><img class="w-[32px] h-[32px]"
                                                src="data/images/login_icons/google_icon.png" /></a>
                                    </button>
                                @endif

                                @if ($urlRediris)
                                    <button type="button"
                                        class="border hover:border-primary flex items-center justify-center rounded-full w-[64px] h-[64px]">
                                        <a href="{{ $urlRediris }}"><img class="w-[32px] h-[32px]"
                                                src="data/images/login_icons/rediris.png" /></a>
                                    </button>
                                @endif
                            @endif

                        </div>

                    </form>

                    @if ($cert_login != '')
                        <div class="text-center p-4"><a href="{{ env('DOMINIO_CERTIFICADO') }}/certificate-access">Acceso
                                mediante
                                Certificado Digital</a></div>
                    @endif

                </div>
            </div>

        </div>

    </section>

    <section class="md:hidden p-[20px]">
        @if ($general_options['poa_logo_1'])
            <img class="mx-auto block max-w-[146px] h-[51px] mb-[15px]" src="{{ $general_options['poa_logo_1'] }}" />
        @endif

        <div class="text-[28px] font-bold text-center mb-[15px]">Inicia sesión</div>

        @if (session('sent_email_recover_password'))
            <div class="bg-[#E7ECF3] py-[12px] px-[27px] rounded-[8px] mb-[15px] text-center">
                <p>Se ha enviado un link para reestablecer la contraseña</p>
                <p>
                    ¿No has recibido nada? <a href="javascript:void(0)" data-email-account="{{ session('email') }}"
                        class="text-color_1 resend-email-confirmation">Reenviar email</a>
                </p>
            </div>
        @endif

        @if (session('link_recover_password_expired'))
            <div class="bg-[#E7ECF3] py-[12px] px-[27px] rounded-[8px] mb-[15px] text-center">
                <p>El link de reestablecimiento de contraseña ha expirado</p>
                <p>
                    <a href="javascript:void(0)" data-email-account="{{ session('email') }}"
                        class="text-color_1 resend-email-confirmation">Reenviar email</a>
                </p>
            </div>
        @endif

        <div class="mb-[25px]">
            <form id="loginFormMobile" action="/login/authenticate" method="POST">
                @csrf
                <div class="flex flex-col mb-[20px]">
                    <label class="px-3 mb-[8px]">Correo</label>
                    <input
                        class="border-[1.5px] border-solid border-primary rounded-full h-[60px] p-3 focus:border-primary "
                        type="text" name="email" />
                </div>

                <div class="flex flex-col mb-[8px]">
                    <label class="px-3 mb-[8px]">Contraseña</label>
                    <input class="border-[1.5px] border-solid border-primary rounded-full h-[60px] p-3" name="password"
                        type="password" />
                </div>

                <a href="{{ route('recover-password') }}"
                    class="text-primary block px-3 mb-[20px] text-[16px]">¿Olvidaste
                    la
                    contraseña?</a>


                <button class="btn bg-primary text-white hover:bg-secondary w-full h-[60px]">Iniciar sesión
                    {{ e_heroicon('arrow-up-right', 'outline') }}</button>

            </form>
        </div>

        @if (
            $urlCas ||
                $urlRediris ||
                $parameters_login_systems['facebook_login_active'] ||
                $parameters_login_systems['twitter_login_active'] ||
                $parameters_login_systems['linkedin_login_active'] ||
                $parameters_login_systems['google_login_active']
        )
            <div class="flex items-center justify-center space-x-2 mb-[25px]">
                <div class="border-t w-full"></div>
                <div>O</div>
                <div class="border-t w-full"></div>
            </div>
        @endif

        <div class="flex justify-center mb-[25px]">

            @if ($urlCas)
                <a href="{{ $urlCas }}">
                    <div
                        class="inline-flex border cursor-pointer hover:border-primary rounded-full items-center justify-center pl-[6px] pr-[14px] py-[6px] gap-2">
                        <div>
                            <img src="{{ asset('data/images/logo_min_boton_login.png') }}"
                                class="w-[40px] h-[40px] mx-auto rounded-full  block" />
                        </div>

                        <div class="border-l h-10"></div>

                        <div>
                            <p class="font-roboto-bold text-black">ACCESO UNIVERSIDAD</p>
                        </div>
                    </div>
                </a>
            @endif

        </div>

        <div class="flex justify-center gap-[32px] flex-wrap">
            @php
                $parameters_login_systems = Cache::get('parameters_login_systems');
            @endphp

            @if ($parameters_login_systems)
                @if ($parameters_login_systems['facebook_login_active'])
                    <button type="button"
                        class="border hover:border-primary flex items-center justify-center rounded-full w-[64px] h-[64px]">
                        <a href="/auth/facebook">
                            <img class="max-w-[45px] max-h-[45px]" src="data/images/login_icons/facebook.png" />
                        </a>
                    </button>
                @endif

                @if ($parameters_login_systems['twitter_login_active'])
                    <button type="button"
                        class="border hover:border-primary flex items-center justify-center rounded-full w-[64px] h-[64px]">
                        <a href="/auth/twitter"><img class="max-w-[32px] max-h-[32px]"
                                src="data/images/login_icons/x_icon.png" /></a>
                    </button>
                @endif

                @if ($parameters_login_systems['linkedin_login_active'])
                    <button type="button"
                        class="border hover:border-primary flex items-center justify-center rounded-full w-[64px] h-[64px]">
                        <a href="/auth/linkedin-openid"><img class="max-w-[32px] max-h-[32px]"
                                src="data/images/login_icons/linkedin_icon.png" /></a>
                    </button>
                @endif

                @if ($parameters_login_systems['google_login_active'])
                    <button type="button"
                        class="border hover:border-primary flex items-center justify-center rounded-full w-[64px] h-[64px]">
                        <a href="/auth/google"><img class="max-w-[32px] max-h-[32px]"
                                src="data/images/login_icons/google_icon.png" /></a>
                    </button>
                @endif
            @endif

        </div>

    </section>
@endsection
