@extends('non_authenticated.app')
@section('content')
    <section class="md:flex hidden">

        <div class="w-1/2">
            <img id="image-background" src="{{ asset('data/images/background_login.png') }}" class="object-cover w-full h-screen" alt="imagen fondo">
        </div>

        <div class="w-1/2 justify-center flex items-center">
            <div class="w-[530px] mb-[25px]">
                <div class="rounded-[20px] border py-[20px] px-[40px]">

                    <div class="text-[28px] font-bold text-center mb-[15px]">Introduce tu email</div>

                    <form id="getEmailDesktop" action="/get-email/add" method="POST">
                        @csrf
                        <div class="mb-[25px]">
                            <div class="flex flex-col mb-[20px]">
                                <label for="email" class="px-3 mb-[8px]">Correo</label>
                                <input
                                    class="border-[1.5px] border-solid border-primary rounded-full p-3 focus:border-primary h-[60px]"
                                    type="text" name="email" value="" />
                            </div>

                            <button type="submit"
                                class="btn bg-primary text-white hover:bg-secondary w-full h-[60px]">Acceder

                                {{ eHeroicon('arrow-up-right', 'outline') }}</button>

                        </div>

                        <input type="hidden" name="nif" id="nif" type="text" value="{{ $nif }}" />
                        <input type="hidden" name="first_name" id="first_name" type="text" value="{{ $first_name }}" />
                        <input type="hidden" name="last_name" id="last_name" type="text" value="{{ $last_name }}" />
                        <input type="hidden" name="is_new" id="is_new" type="text" value="{{ $is_new }}" />

                    </form>

                </div>
            </div>

        </div>

    </section>

    <section class="md:hidden p-[20px]">

        <div class="text-[28px] font-bold text-center mb-[15px]">Introduce tu email</div>

        <div class="mb-[25px]">
            <form id="getEmailMobile" action="/get-email/add/{{ $nif }}" method="POST">
                @csrf

                <div class="mb-[25px]">

                    <div class="flex flex-col mb-[20px]">

                        <div class="flex flex-col mb-[20px]">
                            <label for="email" class="px-3 mb-[8px]">Correo</label>
                            <input
                                class="border-[1.5px] border-solid border-primary rounded-full p-3 focus:border-primary h-[60px]"
                                type="text" name="email" value="" />
                        </div>

                        <button type="submit"
                            class="btn bg-primary text-white hover:bg-secondary w-full h-[60px]">Acceder
                            {{ eHeroicon('arrow-up-right', 'outline') }}</button>

                    </div>

                    <input type="hidden" name="nif" id="nif" type="text" value="{{ $nif }}" />
                    <input type="hidden" name="first_name" id="first_name" type="text" value="{{ $first_name }}" />
                    <input type="hidden" name="last_name" id="last_name" type="text" value="{{ $last_name }}" />
                    <input type="hidden" name="is_new" id="is_new" type="text" value="{{ $is_new }}" />

            </form>
        </div>

    </section>
@endsection
