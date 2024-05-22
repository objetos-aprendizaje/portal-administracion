@php
    $current_module = request()->segment(1);
@endphp

<div class="menu text-white h-screen menu-collapsed menu-show">
    <ul id="main-menu" class="main-menu space-y-2">

        @if (Auth::user()->hasAnyRole(['ADMINISTRATOR']))

            <li class="{{ $current_module === 'administration' ? 'menu-element-selected' : '' }}">
                {{ e_heroicon('pencil', 'outline') }}
                <span>Administración</span>

                <div class="sub-menu hidden sub-menu-administracion">
                    <ul>
                        <li>
                            <a href="{{ route('administracion-general') }}">General</a>
                        </li>
                        <li><a href="{{ route('administration-payments') }}">Pagos</a></li>
                        <li><a href="{{ route('management-permissions') }}">Permisos a gestores</a></li>
                        <li><a href="{{ route('header-pages') }}">Páginas header</a></li>
                        <li><a href="{{ route('footer-pages') }}">Páginas footer</a></li>
                        <li class="disabled"><a href="javascript:void(0)">Credenciales educativas</a></li>
                        <li><a href="{{ route('suggestions-improvements') }}">Sugerencias y mejoras</a></li>
                        <li><a href="{{ route('redirection-queries-educational-program-types') }}">Redirección de
                                consultas
                                de tipos de programas formativos</a></li>
                        <li class="disabled"><a href="javascript:void(0)">Autenticación</a></li>
                        <li><a href="{{ route('lanes-show') }}">Carriles a mostrar</a></li>
                        <li><a href="{{ route('login-systems') }}">Sistemas de inicio de sesión</a></li>
                        <li><a href="{{ route('lms-systems') }}">Sistemas LMS</a></li>
                        <li><a href="{{ route('api-keys') }}">Claves de API</a></li>
                        <li><a href="{{ route('centres') }}">Centros</a></li>
                        <li><a href="{{ route('carrousels') }}">Gestión de carrouseles</a></li>
                    </ul>
                </div>
            </li>
        @endif

        @if (Auth::user()->hasAnyRole(['ADMINISTRATOR', 'MANAGEMENT']))
            <li class="{{ $current_module === 'management' ? 'menu-element-selected' : '' }}">
                {{ e_heroicon('cog-6-tooth', 'outline') }}
                <span>Gestión</span>

                <div class="sub-menu hidden">
                    <ul>
                        <li><a href="{{ route('management-calls') }}">Convocatorias</a></li>
                        <li><a href="{{ route('management-general-configuration') }}">Configuración general</a></li>
                    </ul>
                </div>
            </li>
        @endif

        @if (Auth::user()->hasAnyRole(['ADMINISTRATOR', 'MANAGEMENT']))
            <li class="{{ $current_module === 'cataloging' ? 'menu-element-selected' : '' }}">

                {{ e_heroicon('adjustments-vertical', 'outline') }}
                <span>Catalogación</span>

                <div class="sub-menu hidden">
                    <ul>
                        <li><a href="{{ route('cataloging-categories') }}">Categorías</a></li>
                        <li><a href="{{ route('cataloging-course-types') }}">Tipos de cursos</a></li>
                        <li><a href="{{ route('cataloging-educational-resources') }}">Tipos de recursos educativos</a>
                        </li>
                        <li><a href="{{ route('cataloging-educational-program-types') }}">Tipos de programas</a></li>
                        <li><a href="{{ route('cataloging-competences-learning-results') }}">Competencias y resultados de aprendizaje</a></li>
                        <li><a href="{{ route('cataloging-certification-types') }}">Tipos de certificación</a></li>
                    </ul>
                </div>
            </li>
        @endif

        @if (Auth::user()->hasAnyRole(['ADMINISTRATOR']))
            <li class="{{ $current_module === 'users' ? 'menu-element-selected' : '' }}">
                {{ e_heroicon('user-group', 'outline') }}
                <span>Usuarios</span>

                <div class="sub-menu hidden">
                    <ul>
                        <li><a href="{{ route('list-users') }}">Listado de usuarios</a></li>
                    </ul>
                </div>
            </li>
        @endif

        @if (Auth::user()->hasAnyRole(['ADMINISTRATOR', 'MANAGEMENT', 'TEACHER']))
            <li class="{{ $current_module === 'notifications' ? 'menu-element-selected' : '' }}">
                {{ e_heroicon('bell-alert', 'outline') }}
                <span>Notificaciones</span>

                <div class="sub-menu hidden">
                    <ul>
                        <li><a href="{{ route('notifications-general') }}">Generales</a></li>
                        <li><a href="{{ route('notifications-email') }}">Por correo</a></li>
                        @if (Auth::user()->hasAnyRole(['ADMINISTRATOR']))
                            <li><a href="{{ route('notifications-types') }}">Tipos de notificaciones</a></li>
                            <li><a href="{{ route('notifications-per-users') }}">Notificaciones por usuarios</a></li>
                        @endif
                    </ul>
                </div>
            </li>
        @endif

        @if (Auth::user()->hasAnyRole(['ADMINISTRATOR', 'MANAGEMENT', 'TEACHER']))
            <li class="{{ $current_module === 'learning_objects' ? 'menu-element-selected' : '' }}">
                {{ e_heroicon('academic-cap', 'outline') }}

                <span>Objetos de aprendizaje</span>

                <div class="sub-menu hidden">
                    <ul>
                        <li><a href="{{ route('courses') }}">Cursos</a></li>
                        <li><a href="{{ route('learning-objects-educational-programs') }}">Programas formativos</a>
                        </li>
                        <li><a href="{{ route('learning-objects-educational-resources') }}">Recursos educativos</a>
                        </li>
                        <li><a href="{{ route('learning-objects-educational-resources-per-users') }}">Recursos educativos por usuarios</a>
                        </li>
                    </ul>
                </div>
            </li>
        @endif

        @if (Auth::user()->hasAnyRole(['ADMINISTRATOR', 'MANAGEMENT']))
            <li class="{{ $current_module === 'logs' ? 'menu-element-selected' : '' }}">
                {{ e_heroicon('document-text', 'outline') }}
                <span>Logs</span>

                <div class="sub-menu hidden">
                    <ul>
                        <li><a href="{{ route('list-logs') }}">Listado de logs</a></li>
                    </ul>
                </div>
            </li>
        @endif

        @if (Auth::user()->hasAnyRole(['ADMINISTRATOR', 'MANAGEMENT', 'TEACHER']))
            <li>
                {{ e_heroicon('check-badge', 'outline') }}
                <span>Credenciales</span>

                <div class="sub-menu hidden">
                    <ul>
                        <li><a href="{{ route('credentials-students') }}">Emisión de credenciales a estudiantes</a>
                        </li>
                        <li><a href="{{ route('credentials-teachers') }}">Emisión de credenciales a docentes</a></li>
                        <li class="disabled"><a href="javascript:void(0)">Calificaciones</a></li>
                    </ul>
                </div>
            </li>
        @endif

        @if (Auth::user()->hasAnyRole(['ADMINISTRATOR', 'MANAGEMENT']))
            <li class="{{ $current_module === 'analytics' ? 'menu-element-selected' : '' }}">
                {{ e_heroicon('chart-bar', 'outline') }}
                <span>Analítica</span>

                <div class="sub-menu hidden">
                    <ul>
                        <li class="disabled"><a href="javascript:void(0)">Visitas totales</a></li>
                        <li class="disabled"><a href="javascript:void(0)">Visitas por objetos de aprendizaje</a></li>
                        <li><a href="{{ route('analytics-users') }}">Usuarios registrados</a></li>
                        <li class="disabled"><a href="javascript:void(0)">Abandonos de cursos</a></li>
                    </ul>
                </div>
            </li>
        @endif

        <a class="flex gap-8 {{ $current_module === 'my_profile' ? 'menu-element-selected' : '' }}" href="{{ route('my-profile') }}">
            <li>
                {{ e_heroicon('user', 'outline') }}
                <span>Mi perfil</span>
            </li>
        </a>

        <li id="collapse-expand-menu-btn">
            <div id="arrow-right">
                {{ e_heroicon('arrow-right-circle', 'outline') }}
            </div>
            <div id="arrow-left" class="hidden">
                {{ e_heroicon('arrow-left-circle', 'outline') }}
            </div>
            <span>Contraer</span>
        </li>
    </ul>
</div>
<div id="container-sub-menu"></div>
