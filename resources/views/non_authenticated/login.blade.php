@extends('non_authenticated.app')
@section('content')
    <section class="md:flex hidden">

        <div class="w-1/2">
            <img id="image-background" src="{{ asset('data/images/background_login.png') }}" class="object-cover w-full h-screen">
        </div>

        <div class="w-1/2 justify-center flex items-center">
            <div class="w-[530px] mb-[25px]">
                <div class="rounded-[20px] border py-[20px] px-[40px]">
                    <img class="mx-auto block max-w-[211px] max-h-[80px] mb-[15px]"
                        src="{{ $logo ? asset($logo) : asset('/data/images/logo_login.jpg') }}" />

                    <div class="text-[28px] font-bold text-center mb-[15px]">Inicia sesión</div>

                    <form id="loginFormDesktop" action="/login/authenticate" method="POST">
                        @csrf
                        <div class="mb-[25px]">
                            <div class="flex flex-col mb-[20px]">
                                <label class="px-3 mb-[8px]">Correo</label>
                                <input
                                    class="border-[1.5px] border-solid border-primary rounded-full p-3 focus:border-primary h-[60px]"
                                    type="text" name="email" />
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

                        <div class="flex items-center justify-center space-x-2 mb-[25px]">
                            <div class="border-t w-full"></div>
                            <div>O</div>
                            <div class="border-t w-full"></div>
                        </div>

                        <div class="flex justify-center mb-[25px]">
                            <div
                                class="inline-flex border rounded-full items-center justify-center pl-[6px] pr-[14px] py-[6px] gap-2 cursor-pointer">
                                <div>
                                    <img src="{{ asset('/data/images/logo_min_boton_login.png') }}"
                                        class="w-[40px] h-[40px] mx-auto rounded-full  block" />
                                </div>

                                <div class="border-l h-10"></div>

                                <div>
                                    <p class="font-roboto-bold">ACCESO UNIVERSIDAD</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-center gap-[32px]">
                            @if (Cache::get('parameters_login_systems')['facebook_login_active'])
                                <a href="/auth/facebook">
                                    <img class="w-[60px] h-[60px]" src="data/images/login_icons/facebook.png" />
                                </a>
                            @endif

                            @if (Cache::get('parameters_login_systems')['twitter_login_active'])
                                <a href="/auth/twitter"><img class="w-[60px] h-[60px]"
                                        src="data/images/login_icons/twitter_icon.png" /></a>
                            @endif

                            @if (Cache::get('parameters_login_systems')['linkedin_login_active'])
                                <a href="/auth/linkedin"><img class="w-[60px] h-[60px]"
                                        src="data/images/login_icons/linkedin_icon.png" /></a>
                            @endif

                            @if (Cache::get('parameters_login_systems')['google_login_active'])
                                <a href="/auth/google"><img class="w-[60px] h-[60px]"
                                        src="data/images/login_icons/google_icon.png" /></a>
                            @endif
                        </div>

                    </form>

                </div>
            </div>

        </div>

    </section>

    <section class="md:hidden p-[20px]">
        <img class="mx-auto block max-w-[146px] h-[51px] mb-[15px]"
            src="{{ $logo ? asset($logo) : asset('data/images/logo_login.jpg') }}" />

        <div class="text-[28px] font-bold text-center mb-[15px]">Inicia sesión</div>

        <div class="mb-[25px]">
            <form id="loginFormMobile" action="/login/authenticate" method="POST">
                @csrf
                <div class="flex flex-col mb-[20px]">
                    <label class="px-3 mb-[8px]">Correo</label>
                    <input
                        class="border-[1.5px] border-solid border-primary rounded-full h-[60px] p-3 focus:border-primary "
                        type="text" />
                </div>

                <div class="flex flex-col mb-[8px]">
                    <label class="px-3 mb-[8px]">Contraseña</label>
                    <input class="border-[1.5px] border-solid border-primary rounded-full h-[60px] p-3" type="password" />
                </div>

                <a href="{{ route('recover-password') }}" class="text-primary block px-3 mb-[20px] text-[16px]">¿Olvidaste
                    la
                    contraseña?</a>


                <button class="btn bg-primary text-white hover:bg-secondary w-full h-[60px]">Iniciar sesión
                    {{ e_heroicon('arrow-up-right', 'outline') }}</button>

            </form>
        </div>

        <div class="flex items-center justify-center space-x-2 mb-[25px]">
            <div class="border-t w-full"></div>
            <div>O</div>
            <div class="border-t w-full"></div>
        </div>

        <div class="flex justify-center mb-[25px]">
            <div class="inline-flex border rounded-full items-center justify-center pl-[6px] pr-[14px] py-[6px] gap-2">
                <div>
                    <img src="{{ asset('data/images/logo_min_boton_login.png') }}"
                        class="w-[40px] h-[40px] mx-auto rounded-full  block" />
                </div>

                <div class="border-l h-10"></div>

                <div>
                    <p class="font-roboto-bold">ACCESO UNIVERSIDAD</p>
                </div>
            </div>
        </div>

        <div class="flex justify-center gap-[32px] flex-wrap">
            @if (Cache::get('parameters_login_systems')['facebook_login_active'])
                <a href="/auth/facebook">
                    <img class="max-w-[60px] max-h-[60px]" src="data/images/login_icons/facebook.png" />
                </a>
            @endif

            @if (Cache::get('parameters_login_systems')['twitter_login_active'])
                <a href="/auth/twitter"><img class="max-w-[60px] max-h-[60px]"
                        src="data/images/login_icons/twitter_icon.png" /></a>
            @endif

            @if (Cache::get('parameters_login_systems')['linkedin_login_active'])
                <a href="/auth/linkedin"><img class="max-w-[60px] max-h-[60px]"
                        src="data/images/login_icons/linkedin_icon.png" /></a>
            @endif

            @if (Cache::get('parameters_login_systems')['google_login_active'])
                <a href="/auth/google"><img class="max-w-[60px] max-h-[60px]"
                        src="data/images/login_icons/google_icon.png" /></a>
            @endif
        </div>

    </section>
@endsection
