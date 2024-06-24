<h1>Nuevos programas educativos que te podrían interesar</h1>

<p>Hola, se han dado de alta unos nuevos programas educativos que podrían interesarte en base a tus preferencias</p>

<ul>
    @foreach ($parameters['educational_programs'] as $educationalProgram)
        <li>{{$educationalProgram["title"]}}</li>
    @endforeach
</ul>
