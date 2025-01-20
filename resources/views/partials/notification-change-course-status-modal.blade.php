<div id="notification-change-course-status-modal" data-action="" class="modal">
    <div class="modal-body w-full md:w-[600px]">
        <div class="text-center">
            <h2 class="modal-title">
                Cambio de estado de curso
            </h2>
            <p id="notification-change-course-status-description">
                El curso <span id="notification-change-course-status-name" class="font-roboto-bold"></span> ha cambiado al estado <span
                    id="notification-change-course-status-status" class="font-roboto-bold"></span> el día <span id="notification-change-course-status-date" class="font-roboto-bold"></span>
            </p>
        </div>

        <div class="btn-block">
            <div>
                <a id="notification-change-course-status-url" href="javascript:void(0)"><button data-modal-id="notification-change-course-status-modal"
                    class="btn btn-primary w-[200px]">Ir al curso
                    {{ eHeroicon('arrow-up-right', 'outline') }}</button></a>
            </div>
            <div>
                <button data-modal-id="notification-change-course-status-modal"
                    class="btn btn-secondary w-[200px] close-modal-btn">Cerrar
                    {{ eHeroicon('x-mark', 'outline') }}</button>
            </div>
        </div>

    </div>
</div>
