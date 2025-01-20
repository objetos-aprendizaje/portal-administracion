@extends('non_authenticated.app')
@section('content')
    <section class="md:flex hidden">

        <div class="w-1/2">
            <img id="image-background" src="{{ asset('data/images/background_login.png') }}" class="object-cover w-full h-screen" alt="Imagen de fondo">
        </div>

        <div class="w-1/2 justify-center flex items-center">
            <div class="w-[530px] mb-[25px]">
                <div class="rounded-[20px] border py-[20px] px-[40px]">

                    <div class="text-[28px] font-bold text-center mb-[15px]">Reestablece la contraseña</div>

                    <form id="resetPasswordDesktop" action="/password/reset" method="POST">
                        @csrf
                        <div class="mb-[25px]">
                            <div class="flex flex-col mb-[20px]">

                                <div class="flex flex-col mb-[20px]">
                                    <label class="px-3 mb-[8px]" for="password">Nueva contraseña</label>
                                    <input
                                        class="border-[1.5px] border-solid border-primary rounded-full p-3 focus:border-primary h-[60px]"
                                        type="password" name="password" />

                                </div>

                                <div class="flex flex-col mb-[20px]">

                                    <label class="px-3 mb-[8px]" for="confirm_password">Repite la contraseña</label>
                                    <input
                                        class="border-[1.5px] border-solid border-primary rounded-full p-3 focus:border-primary h-[60px]"
                                        type="password" name="confirm_password" />

                                </div>
                            </div>

                            <button type="submit"
                                class="btn bg-primary text-white hover:bg-secondary w-full h-[60px]">Restablecer
                                contraseña
                                {{ eHeroicon('arrow-up-right', 'outline') }}</button>

                        </div>

                        <p class="text-center">Volver a <a href="{{ route('login') }}">Iniciar sesión</a></p>

                        <input type="hidden" name="token" id="token" value="{{ $token }}" />
                    </form>

                </div>
            </div>

        </div>

    </section>

    <section class="md:hidden p-[20px]">

        <div class="text-[28px] font-bold text-center mb-[15px]">¿Olvidaste la contraseña?</div>

        <div class="mb-[25px]">
            <form id="resetPasswordMobile" action="/reset_password/send" method="POST">
                @csrf

                <div class="mb-[25px]">

                    <div class="flex flex-col mb-[20px]">

                        <div class="flex flex-col mb-[20px]">
                            <label class="px-3 mb-[8px]" for="password">Nueva contraseña</label>
                            <input
                                class="border-[1.5px] border-solid border-primary rounded-full p-3 focus:border-primary h-[60px]"
                                type="password" name="password" />

                        </div>

                        <div class="flex flex-col mb-[20px]">

                            <label class="px-3 mb-[8px]" for="confirm_password">Repite la contraseña</label>
                            <input
                                class="border-[1.5px] border-solid border-primary rounded-full p-3 focus:border-primary h-[60px]"
                                type="password" name="confirm_password" />

                        </div>

                        <button type="submit"
                            class="btn bg-primary text-white hover:bg-secondary w-full h-[60px]">Restablecer contraseña
                            {{ eHeroicon('arrow-up-right', 'outline') }}</button>

                    </div>
                    <p class="text-center">Volver a <a href="{{ route('login') }}">Iniciar sesión</a></p>

            </form>
        </div>

    </section>
@endsection
