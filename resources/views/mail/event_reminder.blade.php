<h2>¡Tu evento es mañana!</h2>

<p>Hola {{ $usuario->name }},</p>

<p>Te recordamos que mañana tienes el evento <strong>{{ $evento->title }}</strong>.</p>

<ul>
    <li><strong>Ciudad:</strong> {{ $evento->city }}</li>
    <li><strong>Lugar:</strong> {{ $evento->venue ?? 'Por confirmar' }}</li>
    <li><strong>Fecha:</strong> {{ $evento->starts_at->format('d/m/Y H:i') }}</li>
</ul>

<p>No olvides tu código de ticket para el check-in:</p>

<h1 style="letter-spacing: 3px;">{{ $codigo }}</h1>

<p>¡Te esperamos!</p>
