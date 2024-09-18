<h1>
    Tu inscripción en el programa formativo ha sido {{ $parameters['status'] == "ACCEPTED" ? "aceptada" : "rechazada"}}
</h1>

<p>
    Tu inscripción en el programa formativo {{ $parameters['educational_program_title'] }} ha sido {{ $parameters['status'] == "ACCEPTED" ? "aceptada" : "rechazada"}}.
</p>
