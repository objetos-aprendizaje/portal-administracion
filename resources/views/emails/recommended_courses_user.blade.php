<h1>Nuevos cursos que te podrían interesar</h1>

<p>Hola, se han dado de alta unos nuevos cursos que podrían interesarte en base a tus preferencias</p>

<ul>
    @foreach ($parameters['courses'] as $course)
        <li>{{$course->title}}</li>
    @endforeach
</ul>
