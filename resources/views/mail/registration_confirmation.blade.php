<h2>¡Inscripción confirmada!</h2>

<p>Hola {{ $usuario->name }},</p>

<p>Te has inscrito correctamente en el evento <strong>{{ $evento->title }}</strong>.</p>

<ul>
    <li><strong>Ciudad:</strong> {{ $evento->city }}</li>
    <li><strong>Lugar:</strong> {{ $evento->venue ?? 'Por confirmar' }}</li>
    <li><strong>Fecha:</strong> {{ $evento->starts_at->format('d/m/Y H:i') }}</li>
</ul>

<p>Tu código de ticket es:</p>

<h1 style="letter-spacing: 3px;">{{ $codigo }}</h1>

<p>Presenta este código el día del evento para el check-in. ¡Nos vemos allí!</p>
