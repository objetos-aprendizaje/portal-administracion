# Portal de Objetos de Aprendizaje (POA)

## Descripción

Este proyecto tiene como objetivo desarrollar el "Portal de Objetos de Aprendizaje" (POA), una plataforma en línea para facilitar la gestión, búsqueda y seguimiento de cursos y recursos educativos (imágenes, videos, iframes, scripts, etc.) por parte de varias universidades participantes. El POA incluye funcionalidades como la recomendación inteligente de recursos educativos y el seguimiento del progreso de los usuarios.

## Características principales

- **Gestión de cursos y recursos:** Alta, inscripción, administración y seguimiento de cursos y objetos de aprendizaje.
- **Recomendación personalizada:** Ofrece recomendaciones de cursos y recursos basadas en las preferencias de los usuarios.
- **Certificación y analítica:** Emite certificados de logros alcanzados y proporciona análisis detallado de los cursos, usuarios y recursos.
- **Plataforma modular:** Compuesta por diferentes módulos funcionales como administración, catalogación, notificaciones, logs, etc.
- **Multiuniversidad:** Cada universidad participante tiene su propia instancia independiente de la plataforma.

## Módulos del sistema

- **Portal web:** La interfaz a través de la cual interactúan los estudiantes.
- **Módulo de Administración:** Gestión de la configuración del portal, permisos, integraciones, notificaciones y pasarela de pago.
- **Módulo de Gestión:** Permite a los gestores manejar los cursos, inscripciones y recursos disponibles en la plataforma.
- **Módulo de Catalogación:** Clasifica y organiza los objetos de aprendizaje en base a diversas categorías.
- **Módulo de Usuarios:** Administra a todos los usuarios registrados en la plataforma, gestionando su acceso y perfiles.
- **Módulo de Notificaciones:** Permite el envío de notificaciones tanto a través del portal web como por correo electrónico.
- **Módulo de Objetos de Aprendizaje:** Gestiona la creación y administración de cursos y programas formativos.
- **Módulo de Búsqueda:** Ofrece una búsqueda avanzada para localizar cursos y recursos utilizando criterios como temáticas, fechas o valoraciones.
- **Módulo de Recomendación:** Proporciona recomendaciones basadas en el perfil y actividades previas de los usuarios.
- **Módulo de Logs:** Almacena todos los logs relativos al uso de la plataforma. Creaciones, actualizaciones y eliminaciones.
- **Módulo de Credenciales:** Emite certificados a los estudiantes que completan con éxito los cursos y programas formativos mediante la API de CertiDigital.
- **Módulo de Analítica:** Proporciona informes detallados sobre la participación y el rendimiento en los cursos.

## Arquitectura

La arquitectura del sistema incluye:
- **Portal Web:** Accesible a usuarios estudiantes.
- **Portal Backend:** Accesible para usuarios administradores, gestores y docentes.

## Documentación
- Toda la documentación se encuentra disponible en [Confluence](https://confluence.um.es/confluence/x/B4BjMw)

## Integración

El sistema está diseñado para integrarse con los LMS de las universidades a través de APIs y servidores de eventos, lo que permite la inscripción automática de usuarios y la sincronización de información de cursos.

## Licencia

Este proyecto se distribuye bajo una licencia de código abierto, y los desarrollos resultantes estarán disponibles para todo el sistema universitario español.

## Contacto

**Responsables del proyecto**:
- Universidad de Murcia.
- Universidad Politécnica de Valencia.
- Universidad de Zaragoza.
- Universidad de Granada.
- Universidad Politécnica de Madrid.
- Universidad de Extremadura.
