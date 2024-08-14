@php
    $current_module = request()->segment(1);
@endphp

<div class="menu text-white h-screen menu-collapsed menu-show">
    <ul id="main-menu" class="main-menu space-y-2">

        @if (Auth::user()->hasAnyRole(['ADMINISTRATOR']))
            <div class="container-menu">
                <li class="{{ $current_module === 'administration' ? 'menu-element-selected' : '' }}">
                    {{ e_heroicon('pencil', 'outline') }}
                    <span>Administración</span>
                    {{ e_heroicon('chevron-down', 'outline', 'close-sub-menu') }}
                    {{ e_heroicon('chevron-up', 'outline', 'open-sub-menu hidden') }}
                </li>
                <div class="sub-menu hidden-opacity transition-all sub-menu-administracion">
                    <ul>
                        <li>
                            <a class="{{ isset($submenuselected) && $submenuselected == 'administracion-general' ? 'submenu-selected' : '' }}" href="{{ route('administracion-general') }}">General</a>
                        </li>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'administration-payments' ? 'submenu-selected' : '' }}" href="{{ route('administration-payments') }}">Pagos</a></li>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'management-permissions' ? 'submenu-selected' : '' }}" href="{{ route('management-permissions') }}">Permisos a gestores</a></li>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'header-pages' ? 'submenu-selected' : '' }}" href="{{ route('header-pages') }}">Páginas header</a></li>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'footer-pages' ? 'submenu-selected' : '' }}" href="{{ route('footer-pages') }}">Páginas footer</a></li>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'suggestions-improvements' ? 'submenu-selected' : '' }}" href="{{ route('suggestions-improvements') }}">Sugerencias y mejoras</a></li>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'redirection-queries-educational-program-types' ? 'submenu-selected' : '' }}" href="{{ route('redirection-queries-educational-program-types') }}">Redirección de
                                consultas
                                de tipos de programas formativos</a></li>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'lanes-show' ? 'submenu-selected' : '' }}" href="{{ route('lanes-show') }}">Carriles a mostrar</a></li>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'login-systems' ? 'submenu-selected' : '' }}" href="{{ route('login-systems') }}">Sistemas de inicio de sesión</a></li>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'lms-systems' ? 'submenu-selected' : '' }}" href="{{ route('lms-systems') }}">Sistemas LMS</a></li>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'api-keys' ? 'submenu-selected' : '' }}" href="{{ route('api-keys') }}">Claves de API</a></li>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'centres' ? 'submenu-selected' : '' }}" href="{{ route('centres') }}">Centros</a></li>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'carrousels' ? 'submenu-selected' : '' }}" href="{{ route('carrousels') }}">Slider y carrousel principal</a></li>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'certidigital' ? 'submenu-selected' : '' }}" href="{{ route('certidigital-configuration') }}">API Certidigital</a></li>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'licenses' ? 'submenu-selected' : '' }}" href="{{ route('licenses') }}">Licencias</a></li>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'administracion-tooltip-texts' ? 'submenu-selected' : '' }}" href="{{ route('tooltip-texts') }}">Textos para tooltips</a></li>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'departments' ? 'submenu-selected' : '' }}" href="{{ route('departments') }}">Departamentos</a></li>
                    </ul>
                </div>
            </div>
        @endif

        @if (Auth::user()->hasAnyRole(['MANAGEMENT']))
            <div class="container-menu">
                <li class="{{ $current_module === 'management' ? 'menu-element-selected' : '' }}">
                    {{ e_heroicon('cog-6-tooth', 'outline') }}
                    <span>Gestión</span>
                    {{ e_heroicon('chevron-down', 'outline', 'close-sub-menu') }}
                    {{ e_heroicon('chevron-up', 'outline', 'open-sub-menu hidden') }}
                </li>
                <div class="sub-menu hidden-opacity transition-all">
                    <ul>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'management-calls' ? 'submenu-selected' : '' }}" href="{{ route('management-calls') }}">Convocatorias</a></li>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'management-general-configuration' ? 'submenu-selected' : '' }}" href="{{ route('management-general-configuration') }}">Configuración general</a></li>
                    </ul>
                </div>
            </div>
        @endif

        @if (Auth::user()->hasAnyRole(['ADMINISTRATOR', 'MANAGEMENT']))
            <div class="container-menu">
                <li class="{{ $current_module === 'cataloging' ? 'menu-element-selected' : '' }}">

                    {{ e_heroicon('adjustments-vertical', 'outline') }}
                    <span>Catalogación</span>
                    {{ e_heroicon('chevron-down', 'outline', 'close-sub-menu') }}
                    {{ e_heroicon('chevron-up', 'outline', 'open-sub-menu hidden') }}
                </li>
                <div class="sub-menu hidden-opacity transition-all">
                    <ul>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'cataloging-categories' ? 'submenu-selected' : '' }}" href="{{ route('cataloging-categories') }}">Categorías</a></li>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'cataloging-course-types' ? 'submenu-selected' : '' }}" href="{{ route('cataloging-course-types') }}">Tipos de cursos</a></li>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'cataloging-educational-resources' ? 'submenu-selected' : '' }}" href="{{ route('cataloging-educational-resources') }}">Tipos de recursos educativos</a>
                        </li>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'cataloging-educational-program-types' ? 'submenu-selected' : '' }}" href="{{ route('cataloging-educational-program-types') }}">Tipos de programas</a></li>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'cataloging-competences-learning-results' ? 'submenu-selected' : '' }}" href="{{ route('cataloging-competences-learning-results') }}">Competencias y resultados de aprendizaje</a></li>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'cataloging-certification-types' ? 'submenu-selected' : '' }}" href="{{ route('cataloging-certification-types') }}">Tipos de certificación</a></li>
                    </ul>
                </div>
            </div>
        @endif

        @if (Auth::user()->hasAnyRole(['ADMINISTRATOR']))
            <div class="container-menu">
                <li class="{{ $current_module === 'users' ? 'menu-element-selected' : '' }}">
                    {{ e_heroicon('user-group', 'outline') }}
                    <span>Usuarios</span>
                    {{ e_heroicon('chevron-down', 'outline', 'close-sub-menu') }}
                    {{ e_heroicon('chevron-up', 'outline', 'open-sub-menu hidden') }}
                </li>
                <div class="sub-menu hidden-opacity transition-all">
                    <ul>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'list-users' ? 'submenu-selected' : '' }}" href="{{ route('list-users') }}">Listado de usuarios</a></li>
                    </ul>
                </div>
            </div>
        @endif

        @if (Auth::user()->hasAnyRole(['MANAGEMENT', 'TEACHER']))
            <div class="container-menu">
                <li class="{{ $current_module === 'notifications' ? 'menu-element-selected' : '' }}">
                    {{ e_heroicon('bell-alert', 'outline') }}
                    <span>Notificaciones</span>
                    {{ e_heroicon('chevron-down', 'outline', 'close-sub-menu') }}
                    {{ e_heroicon('chevron-up', 'outline', 'open-sub-menu hidden') }}
                </li>
                <div class="sub-menu hidden-opacity transition-all">
                    <ul>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'notifications-general' ? 'submenu-selected' : '' }}" href="{{ route('notifications-general') }}">Generales</a></li>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'notifications-email' ? 'submenu-selected' : '' }}" href="{{ route('notifications-email') }}">Por correo</a></li>
                        @if (Auth::user()->hasAnyRole(['ADMINISTRATOR']))
                            <li><a class="{{ isset($submenuselected) && $submenuselected == 'notifications-types' ? 'submenu-selected' : '' }}" href="{{ route('notifications-types') }}">Tipos de notificaciones</a></li>
                            <li><a class="{{ isset($submenuselected) && $submenuselected == 'notifications-per-users' ? 'submenu-selected' : '' }}" href="{{ route('notifications-per-users') }}">Notificaciones por usuarios</a></li>
                        @endif
                    </ul>
                </div>
            </div>
        @endif

        @if (Auth::user()->hasAnyRole(['MANAGEMENT', 'TEACHER']))
            <div class="container-menu">
                <li class="{{ $current_module === 'learning_objects' ? 'menu-element-selected' : '' }}">
                    {{ e_heroicon('academic-cap', 'outline') }}
                    <span>Objetos de aprendizaje</span>
                    {{ e_heroicon('chevron-down', 'outline', 'close-sub-menu') }}
                    {{ e_heroicon('chevron-up', 'outline', 'open-sub-menu hidden') }}
                </li>
                <div class="sub-menu hidden-opacity transition-all">
                    <ul>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'courses' ? 'submenu-selected' : '' }}" href="{{ route('courses') }}">Cursos</a></li>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'learning-objects-educational-programs' ? 'submenu-selected' : '' }}" href="{{ route('learning-objects-educational-programs') }}">Programas formativos</a>
                        </li>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'learning-objects-educational-resources' ? 'submenu-selected' : '' }}" href="{{ route('learning-objects-educational-resources') }}">Recursos educativos</a>
                        </li>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'learning-objects-educational-resources-per-users' ? 'submenu-selected' : '' }}" href="{{ route('learning-objects-educational-resources-per-users') }}">Recursos educativos por usuarios</a>
                        </li>
                    </ul>
                </div>
            </div>
        @endif

        @if (Auth::user()->hasAnyRole(['ADMINISTRATOR', 'MANAGEMENT']))
            <div class="container-menu">
                <li class="{{ $current_module === 'logs' ? 'menu-element-selected' : '' }}">
                    {{ e_heroicon('document-text', 'outline') }}
                    <span>Logs</span>
                    {{ e_heroicon('chevron-down', 'outline', 'close-sub-menu') }}
                    {{ e_heroicon('chevron-up', 'outline', 'open-sub-menu hidden') }}
                </li>
                <div class="sub-menu hidden-opacity transition-all">
                    <ul>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'list-logs' ? 'submenu-selected' : '' }}" href="{{ route('list-logs') }}">Listado de logs</a></li>
                    </ul>
                </div>
            </div>
        @endif

        @if (Auth::user()->hasAnyRole(['MANAGEMENT', 'TEACHER']))
            <div class="container-menu">
                <li class="{{ $current_module === 'credentials' ? 'menu-element-selected' : '' }}">
                    {{ e_heroicon('check-badge', 'outline') }}
                    <span>Credenciales</span>
                    {{ e_heroicon('chevron-down', 'outline', 'close-sub-menu') }}
                    {{ e_heroicon('chevron-up', 'outline', 'open-sub-menu hidden') }}
                </li>
                <div class="sub-menu hidden-opacity transition-all">
                    <ul>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'credentials-students' ? 'submenu-selected' : '' }}" href="{{ route('credentials-students') }}">Emisión de credenciales a estudiantes</a>
                        </li>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'credentials-teachers' ? 'submenu-selected' : '' }}" href="{{ route('credentials-teachers') }}">Emisión de credenciales a docentes</a></li>
                        <li class="disabled"><a href="javascript:void(0)">Calificaciones</a></li>
                    </ul>
                </div>
            </div>
        @endif

        @if (Auth::user()->hasAnyRole(['MANAGEMENT']))
            <div class="container-menu">
                <li class="{{ $current_module === 'analytics' ? 'menu-element-selected' : '' }}">
                    {{ e_heroicon('chart-bar', 'outline') }}
                    <span>Analítica</span>
                    {{ e_heroicon('chevron-down', 'outline', 'close-sub-menu') }}
                    {{ e_heroicon('chevron-up', 'outline', 'open-sub-menu hidden') }}
                </li>
                <div class="sub-menu hidden-opacity transition-all">
                    <ul>
                        <li class="disabled"><a href="javascript:void(0)">Visitas totales</a></li>
                        <li class="disabled"><a href="javascript:void(0)">Visitas por objetos de aprendizaje</a></li>
                        <li><a class="{{ isset($submenuselected) && $submenuselected == 'analytics-users' ? 'submenu-selected' : '' }}" href="{{ route('analytics-users') }}">Usuarios registrados</a></li>
                        <li class="disabled"><a href="javascript:void(0)">Abandonos de cursos</a></li>
                    </ul>
                </div>
            </div>
        @endif

        <div class="container-menu">
            <li class="{{ $current_module === 'my_profile' ? 'menu-element-selected' : '' }}">
                {{ e_heroicon('user', 'outline') }}
                <span>Mi perfil</span>
                {{ e_heroicon('chevron-down', 'outline', 'close-sub-menu') }}
                {{ e_heroicon('chevron-up', 'outline', 'open-sub-menu hidden') }}
            </li>
            <div class="sub-menu hidden-opacity transition-all">
                <ul>
                    <li><a class="{{ isset($submenuselected) && $submenuselected == 'my_profile' ? 'submenu-selected' : '' }}" href="{{ route('my-profile') }}">Mi perfil</a></li>
                </ul>
            </div>
        </div>

        <li id="collapse-expand-menu-btn" class="hidden-collapse-expand-menu-btn">
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
