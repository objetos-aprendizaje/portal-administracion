@extends('layouts.app')

@section('content')
    <div class="flex flex-col md:flex-row gap-12 mb-8">
        <div class="poa-container w-full md:w-1/2 h-[550px]">
            <div class="relative h-full flex justify-between flex-col gap-8">

                <h2>Logo</h2>

                <div class="flex justify-center relative h-full flex-col">

                    <div id="image-logo-poa-container"
                        class=" w-full max-h-[140px] flex justify-center items-center {{ $general_options['poa_logo'] ? '' : 'hidden' }}">
                        <img id="image-logo-poa"
                            src="{{ $general_options['poa_logo'] ? asset($general_options['poa_logo']) : '' }}" alt="Logo POA"
                            class="object-contain w-full h-full" />
                    </div>
                    <div id="no-image-logo-poa-container"
                        class="bg-[#F5F6F4] w-full h-full flex justify-center items-center {{ $general_options['poa_logo'] ? 'hidden' : '' }}">
                        <div>
                            <svg width="80" height="80" viewBox="0 0 80 80" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <g id="image">
                                    <path id="Vector"
                                        d="M70 63.3333V16.6667C70 13 67 10 63.3333 10H16.6667C13 10 10 13 10 16.6667V63.3333C10 67 13 70 16.6667 70H63.3333C67 70 70 67 70 63.3333ZM29.6667 46.6L36.6667 55.0333L47 41.7333C47.6667 40.8667 49 40.8667 49.6667 41.7667L61.3667 57.3667C62.2 58.4667 61.4 60.0333 60.0333 60.0333H20.0667C18.6667 60.0333 17.9 58.4333 18.7667 57.3333L27.0667 46.6667C27.7 45.8 28.9667 45.7667 29.6667 46.6Z"
                                        fill="#CBD0C8" />
                                </g>
                            </svg>
                        </div>

                    </div>
                    <small class="text-center text-[#C7C7C7] mt-8">*Dimensiones: Alto: 50px x Ancho: 300px. Formato: PNG,
                        JPG. Tam. Máx.: 1MB</small>
                </div>

                <div class="w-full">
                    <div class="flex gap-6 justify-center">
                        <button id="restore-logo-image-btn" class="btn btn-primary">Restaurar
                            {{ e_heroicon('arrow-path', 'outline') }}</button>
                        <input type="file" accept="image/*" name="logo-poa" id="logo-poa" hidden />
                        <label class="btn btn-primary" for="logo-poa">Subir
                            {{ e_heroicon('arrow-up-tray', 'outline') }}</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="poa-container w-full md:w-1/2 h-[550px]">
            <div class="relative h-full flex flex-col">
                <h2>Paleta de colores</h2>

                <div class="h-full">
                    <div class="color-definition">
                        <div class="coloris-button">
                            <input value="{{ $general_options['color_1'] }}" id="color-1" class="coloris cursor-pointer" type="text"
                                data-coloris>
                        </div>
                        <div>
                            <label for="color-1" class="text-primary font-roboto-bold">Color Primario (Títulos y
                                Botones):</label> Este color
                            se utiliza para los títulos principales (h1, h2, h3, h4) y el fondo de los botones. Modifica la
                            apariencia de los elementos más destacados y llamativos de la interfaz.
                        </div>
                    </div>

                    <div class="color-definition">
                        <div class="coloris-button">
                            <input value="{{ $general_options['color_2'] }}" id="color-2" class="coloris" type="text"
                                data-coloris>
                        </div>
                        <div>
                            <label for="color-2" class="text-primary font-roboto-bold">Color Secundario (Subtítulos y
                                Botones Hover):</label> Este color se utiliza para los títulos de nivel inferior (h5), el
                            efecto hover del fondo de los botones principales y el fondo del bloque de categorías.
                            Proporciona un contraste con el color primario y se utiliza en elementos interactivos y áreas de
                            contenido destacado.
                        </div>
                    </div>

                    <div class="color-definition">
                        <div class="coloris-button">
                            <input value="{{ $general_options['color_3'] }}" id="color-3" class="coloris" type="text"
                                data-coloris>
                        </div>
                        <div>
                            <label for="color-3" class="text-primary font-roboto-bold">Color Terciario (Textos
                                Principales):</label> Este color
                            se utiliza para los textos principales, incluyendo los párrafos (p) y las etiquetas (label).
                            Define la apariencia de la mayoría del texto en la interfaz
                        </div>
                    </div>

                    <div class="color-definition">
                        <div class="coloris-button">
                            <input value="{{ $general_options['color_4'] }}" id="color-4" class="coloris" type="text"
                                data-coloris>
                        </div>
                        <div>
                            <label for="color-4" class="text-primary font-roboto-bold">Color Cuaternario (Textos Secundarios):</label>
                            Este color se utiliza para los textos secundarios que son más pequeños que los párrafos y menos
                            destacados. Se utiliza para proporcionar información adicional o secundaria, y para elementos de
                            la interfaz que no necesitan destacar tanto como los elementos principales.
                        </div>
                    </div>
                </div>

                <div class="flex justify-center">
                    <button id="update-colors-btn" class="btn btn-primary w-48">Guardar
                        {{ e_heroicon('paper-airplane', 'outline') }}</button>
                </div>

            </div>

        </div>
    </div>


    <div class="poa-container mb-8">
        <h2>Configuración general</h2>

        <form id="general-configuration-form">
            @csrf
            <div class="checkbox mb-2">
                <label for="learning_objects_appraisals" class="inline-flex relative items-center cursor-pointer">
                    <input {{ $general_options['learning_objects_appraisals'] ? 'checked' : '' }} type="checkbox"
                        id="learning_objects_appraisals" name="learning_objects_appraisals" class="sr-only peer">
                    <div
                        class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                    </div>
                    <div class="checkbox-name">Activar valoraciones de objetos de aprendizaje</div>
                </label>
            </div>

            <div class="checkbox mb-2">
                <label for="payment_gateway" class="inline-flex relative items-center cursor-pointer">
                    <input {{ $general_options['payment_gateway'] ? 'checked' : '' }} type="checkbox" id="payment_gateway"
                        name="payment_gateway" class="sr-only peer">
                    <div
                        class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                    </div>
                    <div class="checkbox-name">Activar pagos</div>
                </label>
            </div>

            <div class="checkbox mb-2">
                <label for="operation_by_calls" class="inline-flex relative items-center cursor-pointer">
                    <input {{ $general_options['operation_by_calls'] ? 'checked' : '' }} type="checkbox"
                        id="operation_by_calls" name="operation_by_calls" class="sr-only peer">
                    <div
                        class="checkbox-switch peer-checked:bg-primary peer-checked:after:border-white peer-checked:after:translate-x-full">
                    </div>
                    <div class="checkbox-name">Funcionamiento en base a convocatorias</div>
                </label>
            </div>

            <button type="submit" class="btn btn-primary mt-4">Guardar
                {{ e_heroicon('paper-airplane', 'outline') }}</button>

        </form>


    </div>

    <div class="poa-container mb-8">
        <h2>Datos de la Universidad</h2>
        <form id="university-info-form">
            @csrf

            <div class="poa-form">

                <div class="field">
                    <div class="label-container label-center">
                        <label for="company_name">Razón social</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['company_name'] }}" placeholder="Universidad de Madrid"
                            class="poa-input" type="text" id="company_name" name="company_name" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="commercial_name">Nombre comercial</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['commercial_name'] }}" placeholder="Universidad de Madrid"
                            type="text" id="commercial_name" name="commercial_name" class="poa-input" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="cif">CIF</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['cif'] }}" placeholder="X12345678" type="text"
                            id="cif" class="poa-input" name="cif" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="fiscal_domicile">Domicilio fiscal</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['fiscal_domicile'] }}" class="poa-input"
                            placeholder="Gran Vía, 1" type="text" id="fiscal_domicile" name="fiscal_domicile" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="work_center_address">Domicilio centro de trabajo</label>
                    </div>

                    <div class="content-container little">
                        <input value="{{ $general_options['work_center_address'] }}"
                            placeholder="Paseo de la Castellana, 1" type="text" id="work_center_address"
                            name="work_center_address" class="poa-input" />
                    </div>

                </div>

                <button type="submit" class="btn btn-primary">Guardar
                    {{ e_heroicon('paper-airplane', 'outline') }}</button>

            </div>
        </form>

    </div>

    <div class="poa-container mb-8">
        <h2>Configuración del servidor de correo SMTP</h2>

        <form id="email-server-form">
            @csrf
            <div class="poa-form">
                <div class="field">
                    <div class="label-container label-center">
                        <label for="server">Servidor SMTP (host)</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['smtp_server'] }}" class="poa-input"
                            placeholder="smtp.ejemplo.com" type="text" id="server" name="server" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="port">Puerto</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['smtp_port'] }}" class="poa-input" placeholder="587"
                            type="number" id="port" name="port" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="username">Usuario</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['smtp_user'] }}" class="poa-input" placeholder="tu_usuario"
                            type="text" id="username" name="username" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="password">Contraseña</label>
                    </div>
                    <div class="content-container little">
                        <input placeholder="*********" class="poa-input" type="password" id="password"
                            name="password" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="username">Nombre de envío</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['smtp_name_from'] }}" class="poa-input"
                            placeholder="Portal de Objetos de Aprendizaje" type="text" id="smtp_name_from"
                            name="smtp_name_from" />
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Guardar
                    {{ e_heroicon('paper-airplane', 'outline') }}</button>

            </div>

        </form>

    </div>

    <div class="poa-container mb-8">
        <h2>Scripts para Analytics</h2>

        <p class="mb-2">Insertar tus scripts personalizados que se cargarán en el

            &lt;head&gt;
            de tu sitio web. Utilízalo para integrar herramientas como Google Analytics, Google Tag Manager, el píxel
            de Facebook y otros servicios de seguimiento o análisis.
        </p>

        <textarea rows="7" id="scripts-input" class="poa-input">{{ $general_options['scripts'] }}</textarea>

        <button type="button" class="btn btn-primary" id="save-scripts-btn">Guardar
            {{ e_heroicon('paper-airplane', 'outline') }}</button>
    </div>

    <div class="poa-container mb-8">
        <h2>Redes sociales</h2>

        <form id="rrss-form">
            @csrf

            <div class="poa-form">
                <div class="field">
                    <div class="label-container label-center">
                        <label for="facebook_url">Facebook</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['facebook_url'] }}" placeholder="facebook.com"
                            class="poa-input" type="text" id="facebook_url" name="facebook_url" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="x_url">X</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['x_url'] }}" placeholder="x.com" class="poa-input"
                            type="text" id="x_url" name="x_url" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="youtube_url">YouTube</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['youtube_url'] }}" placeholder="youtube.com" class="poa-input"
                            type="text" id="youtube_url" name="youtube_url" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="instagram_url">Instagram</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['instagram_url'] }}" placeholder="instagram.com"
                            class="poa-input" type="text" id="instagram_url" name="instagram_url" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="telegram_url">Telegram</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['telegram_url'] }}" placeholder="telegram.me"
                            class="poa-input" type="text" id="telegram_url" name="telegram_url" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="linkedin_url">Linkedin</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['linkedin_url'] }}" placeholder="linkedin.com"
                            class="poa-input" type="text" id="linkedin_url" name="linkedin_url" />
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="save-rrss-btn">Guardar
                {{ e_heroicon('paper-airplane', 'outline') }}</button>

        </form>
    </div>

    <div class="poa-container mb-8">
        <h2>Configuración por defecto del carrousel de inicio</h2>

        <p class="mt-2 mb-4">
            En caso de que no haya definido ningún curso para que aparezca destacado en el carrousel, se mostrará en primera
            posición la siguiente imagen junto con un su título y descripción.
        </p>

        <form id="carrousel-default-config-form">
            @csrf
            <div class="poa-form">
                <div class="field">
                    <div class="label-container">
                        <label for="carrousel_image_input_file">Imagen</label>
                    </div>
                    <div class="content-container little">
                        <div class="poa-input-image">
                            <img id="carrousel_image_path"
                                src="{{ $general_options['carrousel_image_path'] ? asset($general_options['carrousel_image_path']) : env('NO_IMAGE_SELECTED_PATH') }}" />

                            <div class="select-file-container">
                                <input accept="image/*" type="file" id="carrousel_image_input_file"
                                    name="carrousel_image_input_file" class="hidden" />

                                <div class="flex items-center gap-[20px]">
                                    <label for="carrousel_image_input_file" class="btn btn-rectangular">
                                        Subir {{ e_heroicon('arrow-up-tray', 'outline') }}
                                    </label>

                                    <span class="image-name link-label">Ningún archivo seleccionado</span>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="carrousel_title">Título</label>
                    </div>
                    <div class="content-container little">
                        <input placeholder="Título" class="poa-input" type="text" id="carrousel_title"
                            value="{{ $general_options['carrousel_title'] }}" name="carrousel_title" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="carrousel_description">Descripción</label>
                    </div>
                    <div class="content-container little">
                        <input placeholder="Descripción" value="{{ $general_options['carrousel_description'] }}"
                            class="poa-input" type="text" id="carrousel_description" name="carrousel_description" />
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="save-carrousel-form-btn">Guardar
                {{ e_heroicon('paper-airplane', 'outline') }}</button>
        </form>
    </div>

    <div class="poa-container mb-8">
        <h2>Configuración de la tipografía</h2>

        <p class="mt-2 mb-4">
            El portal emplea tres estilos de fuentes: Regular, Medium y Bold. Cada uno de estos estilos puede cargarse en
            cinco formatos distintos: .eot, .svg, .ttf, .woff y .woff2.
            Aunque se sugiere adjuntar las fuentes en todos los formatos para asegurar la compatibilidad con todos los
            navegadores, no es obligatorio hacerlo. Puedes elegir subir las fuentes en los formatos que prefieras.
        </p>

        <p>
            Sin embargo, ten en cuenta que si un navegador requiere un formato de fuente que no has subido, el navegador
            representará la web con la tipografía que tenga configurada por defecto.
        </p>

        <div class="poa-form">
            <div class="accordion">

                <div class="accordion-item group" tabindex="1">
                    <div class="accordion-header">
                        <h3>Tipografía regular</h3>
                        <div class="">
                            {{ e_heroicon('chevron-down', 'outline') }}
                        </div>
                    </div>

                    <div class="accordion-uncollapsed">
                        <div class="accordion-body">

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="truetype_regular_file_path">Truetype (.ttf)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="truetype_regular_file_path"
                                                data-font="truetype_regular_file_path" class="hidden input-font"
                                                name="truetype_regular_file_path" accept=".ttf">
                                            <label for="truetype_regular_file_path"
                                                class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ e_heroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name truetype_regular_file_path link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>

                                    <div class="truetype_regular_file_path_buttons">
                                        <a id="url_truetype_regular_file_path" data-font="truetype_regular_file_path"
                                            class="{{ $general_options['truetype_regular_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['truetype_regular_file_path'] ? '/' . $general_options['truetype_regular_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="truetype_regular_file_path"
                                            class="{{ $general_options['truetype_regular_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="woff_regular_file_path">Woff (.woff)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file  select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="woff_regular_file_path" class="hidden input-font"
                                                data-font="woff_regular_file_path" name="woff_regular_file_path"
                                                accept=".woff">
                                            <label for="woff_regular_file_path"
                                                class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ e_heroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name woff_regular_file_path link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>

                                    <div class="woff_regular_file_path_buttons">
                                        <a id="url_woff_regular_file_path" data-font="woff_regular_file_path"
                                            class="{{ $general_options['woff_regular_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['woff_regular_file_path'] ? '/' . $general_options['woff_regular_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="woff_regular_file_path"
                                            class="{{ $general_options['woff_regular_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="woff2_regular_file_path">Woff2 (.woff2)</label>
                                </div>

                                <div class="content-container">
                                    <div class="poa-input-file select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="woff2_regular_file_path" class="hidden input-font"
                                                name="woff2_regular_file_path" accept=".woff2"
                                                data-font="woff2_regular_file_path">
                                            <label for="woff2_regular_file_path"
                                                class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ e_heroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name woff2_regular_file_path link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="woff2_regular_file_path_buttons">
                                        <a id="url_woff2_regular_file_path" data-font="woff2_regular_file_path"
                                            class="{{ $general_options['woff2_regular_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['woff2_regular_file_path'] ? '/' . $general_options['woff2_regular_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="woff2_regular_file_path"
                                            class="{{ $general_options['woff2_regular_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="embedded_opentype_regular_file_path">embedded-opentype (.eot)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file  select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="embedded_opentype_regular_file_path"
                                                class="hidden input-font" name="embedded_opentype_regular_file_path"
                                                data-font="embedded_opentype_regular_file_path" accept=".eot">
                                            <label for="embedded_opentype_regular_file_path"
                                                class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ e_heroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name link-label embedded_opentype_regular_file_path">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="embedded_opentype_regular_file_path_buttons">
                                        <a id="embedded_opentype_regular_file_path"
                                            data-font="embedded_opentype_regular_file_path"
                                            class="{{ $general_options['embedded_opentype_regular_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['embedded_opentype_regular_file_path'] ? '/' . $general_options['embedded_opentype_regular_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="embedded_opentype_regular_file_path"
                                            class="{{ $general_options['embedded_opentype_regular_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="opentype_regular_input_file">opentype (.otf)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="opentype_regular_input_file"
                                                class="hidden input-font" name="opentype_regular_input_file"
                                                accept=".otf" data-font="opentype_regular_input_file">
                                            <label for="opentype_regular_input_file"
                                                class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ e_heroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name link-label opentype_regular_input_file">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>

                                    <div class="opentype_regular_input_file_buttons">
                                        <a id="embedded_opentype_regular_file_path"
                                            data-font="embedded_opentype_regular_file_path"
                                            class="{{ $general_options['opentype_regular_input_file'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['opentype_regular_input_file'] ? '/' . $general_options['opentype_regular_input_file'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="opentype_regular_input_file"
                                            class="{{ $general_options['opentype_regular_input_file'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="svg_regular_file_path">svg (.svg)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file  select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="svg_regular_file_path" class="hidden input-font"
                                                name="svg_regular_file_path" accept=".svg"
                                                data-font="svg_regular_file_path">
                                            <label for="svg_regular_file_path" class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ e_heroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name link-label svg_regular_file_path">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="svg_regular_file_path_buttons">
                                        <a id="embedded_opentype_regular_file_path"
                                            data-font="embedded_opentype_regular_file_path"
                                            class="{{ $general_options['svg_regular_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['svg_regular_file_path'] ? '/' . $general_options['svg_regular_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="svg_regular_file_path"
                                            class="{{ $general_options['svg_regular_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="accordion-item group" tabindex="1">
                    <div class="accordion-header">
                        <h3>Tipografía medium</h3>
                        <div class="group transition-all duration-500 group-focus:-rotate-180">
                            {{ e_heroicon('chevron-down', 'outline') }}
                        </div>
                    </div>

                    <div class="accordion-collapsed">
                        <div class="accordion-body">
                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="truetype_medium_file_path">Truetype (.ttf)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file  select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="truetype_medium_file_path"
                                                class="hidden input-font" name="truetype_medium_file_path" accept=".ttf"
                                                data-font="truetype_medium_file_path">
                                            <label for="truetype_medium_file_path"
                                                class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ e_heroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="truetype_medium_file_path_buttons">
                                        <a id="embedded_opentype_regular_file_path"
                                            data-font="embedded_opentype_regular_file_path"
                                            class="{{ $general_options['truetype_medium_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['truetype_medium_file_path'] ? '/' . $general_options['truetype_medium_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="truetype_medium_file_path"
                                            class="{{ $general_options['truetype_medium_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="woff_medium_file_path">Woff (.woff)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="woff_medium_file_path" class="hidden input-font"
                                                name="woff_medium_file_path" accept=".woff"
                                                data-font="woff_medium_file_path">
                                            <label for="woff_medium_file_path" class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ e_heroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="woff_medium_file_path_buttons">
                                        <a id="embedded_opentype_regular_file_path"
                                            data-font="embedded_opentype_regular_file_path"
                                            class="{{ $general_options['woff_medium_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['woff_medium_file_path'] ? '/' . $general_options['woff_medium_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="woff_medium_file_path"
                                            class="{{ $general_options['woff_medium_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="woff2_medium_file_path">Woff2 (.woff2)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="woff2_medium_file_path" class="hidden input-font"
                                                name="woff2_medium_file_path" accept=".woff2"
                                                data-font="woff2_medium_file_path">
                                            <label for="woff2_medium_file_path"
                                                class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ e_heroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="woff2_medium_file_path_buttons">
                                        <a id="woff2_medium_file_path" data-font="woff2_medium_file_path"
                                            class="{{ $general_options['woff2_medium_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['woff2_medium_file_path'] ? '/' . $general_options['woff2_medium_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="woff2_medium_file_path"
                                            class="{{ $general_options['woff2_medium_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="embedded_opentype_medium_file_path">embedded-opentype (.eot)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file  select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="embedded_opentype_medium_file_path"
                                                class="hidden input-font" name="embedded_opentype_medium_file_path"
                                                accept=".eot" data-font="embedded_opentype_medium_file_path">
                                            <label for="embedded_opentype_medium_file_path"
                                                class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ e_heroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="embedded_opentype_medium_file_path_buttons">
                                        <a id="embedded_opentype_regular_file_path"
                                            data-font="embedded_opentype_regular_file_path"
                                            class="{{ $general_options['embedded_opentype_medium_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['embedded_opentype_medium_file_path'] ? '/' . $general_options['embedded_opentype_medium_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="embedded_opentype_medium_file_path"
                                            class="{{ $general_options['embedded_opentype_medium_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="opentype_medium_file_path">opentype (.otf)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file  select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="opentype_medium_file_path"
                                                class="hidden input-font" name="opentype_medium_file_path" accept=".otf"
                                                data-font="opentype_medium_file_path">
                                            <label for="opentype_medium_file_path"
                                                class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ e_heroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name opentype_medium_file_path link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="opentype_medium_file_path_buttons">
                                        <a id="opentype_medium_file_path" data-font="opentype_medium_file_path"
                                            class="{{ $general_options['opentype_medium_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['opentype_medium_file_path'] ? '/' . $general_options['opentype_medium_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="opentype_medium_file_path"
                                            class="{{ $general_options['opentype_medium_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="svg_medium_file_path">svg (.svg)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="svg_medium_file_path" class="hidden input-font"
                                                name="svg_medium_file_path" accept=".svg"
                                                data-font="svg_medium_file_path">
                                            <label for="svg_medium_file_path" class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ e_heroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name svg_medium_file_path link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="svg_medium_file_path_buttons">
                                        <a id="svg_medium_file_path" data-font="svg_medium_file_path"
                                            class="{{ $general_options['svg_medium_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['svg_medium_file_path'] ? '/' . $general_options['svg_medium_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="svg_medium_file_path"
                                            class="{{ $general_options['svg_medium_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>

                <div class="accordion-item group" tabindex="1">
                    <div class="accordion-header">
                        <h3>Tipografía bold</h3>
                        <div class="">
                            {{ e_heroicon('chevron-down', 'outline') }}
                        </div>
                    </div>

                    <div class="accordion-collapsed">
                        <div class="accordion-body">

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="truetype_bold_file_path">Truetype (.ttf)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file  select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="truetype_bold_file_path" class="hidden input-font"
                                                name="truetype_bold_file_path" accept=".ttf"
                                                data-font="truetype_bold_file_path">
                                            <label for="truetype_bold_file_path"
                                                class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ e_heroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name truetype_bold_file_path link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="truetype_bold_file_path_buttons">
                                        <a id="embedded_opentype_regular_file_path"
                                            data-font="embedded_opentype_regular_file_path"
                                            class="{{ $general_options['truetype_bold_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['truetype_bold_file_path'] ? '/' . $general_options['truetype_bold_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="truetype_bold_file_path"
                                            class="{{ $general_options['truetype_bold_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="woff_bold_file_path">Woff (.woff)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file  select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="woff_bold_file_path" class="hidden input-font"
                                                name="woff_bold_file_path" accept=".woff"
                                                data-font="woff_bold_file_path">
                                            <label for="woff_bold_file_path" class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ e_heroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name woff_bold_file_path link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="woff_bold_file_path_buttons">
                                        <a id="embedded_opentype_regular_file_path"
                                            data-font="embedded_opentype_regular_file_path"
                                            class="{{ $general_options['woff_bold_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['woff_bold_file_path'] ? '/' . $general_options['woff_bold_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="woff_bold_file_path"
                                            class="{{ $general_options['woff_bold_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="woff2_bold_file_path">Woff2 (.woff2)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="woff2_bold_file_path" class="hidden input-font"
                                                name="woff2_bold_file_path" accept=".woff2"
                                                data-font="woff2_bold_file_path">
                                            <label for="woff2_bold_file_path" class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ e_heroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name woff2_bold_file_path link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="woff2_bold_file_path_buttons">
                                        <a id="woff2_bold_file_path" data-font="woff2_bold_file_path"
                                            class="{{ $general_options['woff2_bold_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['woff2_bold_file_path'] ? '/' . $general_options['woff2_bold_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="woff2_bold_file_path"
                                            class="{{ $general_options['woff2_bold_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="embedded_opentype_bold_file_path">embedded-opentype (.eot)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file  select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="embedded_opentype_bold_file_path"
                                                class="hidden input-font" name="embedded_opentype_bold_file_path"
                                                accept=".eot" data-font="embedded_opentype_bold_file_path">
                                            <label for="embedded_opentype_bold_file_path"
                                                class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ e_heroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="embedded_opentype_bold_file_path_buttons">
                                        <a id="embedded_opentype_bold_file_path"
                                            data-font="embedded_opentype_bold_file_path"
                                            class="{{ $general_options['embedded_opentype_bold_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['embedded_opentype_bold_file_path'] ? '/' . $general_options['embedded_opentype_bold_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="embedded_opentype_bold_file_path"
                                            class="{{ $general_options['embedded_opentype_bold_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="opentype_bold_file_path">opentype (.otf)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file  select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="opentype_bold_file_path" class="hidden input-font"
                                                name="opentype_bold_file_path" accept=".otf"
                                                data-font="opentype_bold_file_path">
                                            <label for="opentype_bold_file_path"
                                                class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ e_heroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name opentype_bold_file_path link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="opentype_bold_file_path_buttons">
                                        <a id="embedded_opentype_regular_file_path"
                                            data-font="embedded_opentype_regular_file_path"
                                            class="{{ $general_options['opentype_bold_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['opentype_bold_file_path'] ? '/' . $general_options['opentype_bold_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="opentype_bold_file_path"
                                            class="{{ $general_options['opentype_bold_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <div class="label-container label-center">
                                    <label for="svg_bold_file_path">svg (.svg)</label>
                                </div>
                                <div class="content-container">
                                    <div class="poa-input-file select-file-container">
                                        <div class="flex-none">
                                            <input type="file" id="svg_bold_file_path" class="hidden input-font"
                                                name="svg_bold_file_path" accept=".svg" data-font="svg_bold_file_path">
                                            <label for="svg_bold_file_path" class="btn btn-rectangular btn-input-file">
                                                Seleccionar archivo {{ e_heroicon('arrow-up-tray', 'outline') }}
                                            </label>
                                        </div>
                                        <div class="file-name svg_bold_file_path link-label">
                                            Ningún archivo seleccionado
                                        </div>
                                    </div>
                                    <div class="svg_bold_file_path_buttons">
                                        <a id="embedded_opentype_regular_file_path"
                                            data-font="embedded_opentype_regular_file_path"
                                            class="{{ $general_options['svg_bold_file_path'] ? '' : 'hidden' }} link-label download-font"
                                            target="new_blank"
                                            href="{{ $general_options['svg_bold_file_path'] ? '/' . $general_options['svg_bold_file_path'] : 'javascript:void(0)' }}"
                                            download>Descargar</a>


                                        <a data-font="svg_bold_file_path"
                                            class="{{ $general_options['svg_bold_file_path'] ? '' : 'hidden' }} link-label delete-font"
                                            target="new_blank" href="javascript:void(0)">Eliminar</a>
                                    </div>
                                </div>
                            </div>


                        </div>
                    </div>

                </div>

            </div>


        </div>


    </div>
@endsection
