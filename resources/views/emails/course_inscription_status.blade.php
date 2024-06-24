<h1>
    Tu inscripción en el curso ha sido {{ $parameters['status'] == "ACCEPTED" ? "aceptada" : "rechazada"}}
</h1>

<p>
    Tu inscripción en el curso {{ $parameters['course_title'] }} ha sido {{ $parameters['status'] == "ACCEPTED" ? "aceptada" : "rechazada"}}.
</p>
