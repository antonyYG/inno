@php
    /*
     |--------------------------------------------------------------
     | MEMBRETE (imagen de fondo A4 completa)
     |--------------------------------------------------------------
     | Guárdalo en: public/img/membrete.png   (o .jpg)
     | Tamaño recomendado: 1654 x 2339 px (A4 a 200 dpi)
     */
    $membreteFile = collect(['membrete.png', 'membrete.jpg', 'membrete.jpeg'])
        ->map(fn($f) => public_path('img/' . $f))
        ->first(fn($p) => file_exists($p));

    $membrete = null;
    if ($membreteFile) {
        $ext = pathinfo($membreteFile, PATHINFO_EXTENSION) === 'png' ? 'png' : 'jpeg';
        $membrete = 'data:image/' . $ext . ';base64,' . base64_encode(file_get_contents($membreteFile));
    }

    // Firma opcional: public/img/firma.png
    $firmaFile = public_path('img/firma.png');
    $firma = file_exists($firmaFile)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($firmaFile))
        : null;

    /* ---- Márgenes seguros del texto (ajústalos a tu membrete) ---- */
    $mTop    = '58mm';   // espacio para logo / encabezado
    $mBottom = '45mm';   // espacio para pie de página / firma
    $mSide   = '24mm';

    /* ---- Datos ---- */
    $nombreCompleto = trim(($user->nombre ?? '') . ' ' . ($user->apellidos ?? ''));
    $dni         = $user->dni ?? '________';
    $carrera     = $user->carrera ?? '________________';
    $institucion = $user->institucion ?? '________';
    $semestre    = $user->semestre ?? '__';
    $area        = $user->area ?? '________';
    $cargo       = $user->cargo ?? '________';
    $fechaInicio = $fecha_inicio ?? '__/__/____';
    $fechaFin    = $fecha_fin ?? '__/__/____';
    $horasSemana = $horas_semanales ?? '__';
    $funciones   = $funciones ?? [];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado de Prácticas</title>
    <style>
        @page { size: A4 portrait; margin: 0; }

        html, body { margin: 0; padding: 0; }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #222;
        }

        .page {
            position: relative;
            width: 210mm;
            height: 296mm;      /* 1mm menos para evitar página en blanco */
            overflow: hidden;
        }

        /* Membrete de fondo */
        .bg {
            position: absolute;
            top: 0; left: 0;
            width: 210mm;
            height: 296mm;
        }

        /* Caja de texto: no puede salirse de la hoja */
        .content {
            position: absolute;
            top: {{ $mTop }};
            left: {{ $mSide }};
            right: {{ $mSide }};
            bottom: {{ $mBottom }};
            overflow: hidden;
        }

        .title {
            text-align: center;
            font-size: 21px;
            font-weight: bold;
            line-height: 1.4;
            color: #2b2b2b;
            margin: 0 0 8mm 0;
        }

        p {
            margin: 0 0 5mm 0;
            font-size: 11px;
            line-height: 1.65;
            text-align: justify;
        }

        .name {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 7mm 0;
        }

        ul { margin: 0 0 5mm 0; padding-left: 6mm; }
        li {
            font-size: 11px;
            line-height: 1.6;
            text-align: justify;
            margin-bottom: 3mm;
        }

        /* Firma y fecha (parte baja de la caja) */
        .sign {
            position: absolute;
            left: 0; right: 0; bottom: 0;
            height: 28mm;
        }
        .sign-left  { position: absolute; left: 0; bottom: 0; }
        .sign-right { position: absolute; right: 0; bottom: 4mm; font-size: 12px; }
        .sign-img   { position: absolute; left: 8mm; bottom: 8mm; width: 38mm; height: auto; }
        .sign-name  { font-size: 12px; font-weight: bold; color: #1b2a41; }
        .sign-role  { font-size: 10px; color: #6b7280; }

        /* Fallback si no hay membrete */
        .fallback-top { position: absolute; top: 0; left: 0; right: 0; height: 10mm; background: #17365d; }
        .fallback-bot { position: absolute; bottom: 0; left: 0; right: 0; height: 10mm; background: #17365d; }
        .fallback-logo { position: absolute; top: 18mm; left: {{ $mSide }}; font-size: 22px; font-weight: bold; color: #17365d; }
    </style>
</head>
<body>
<div class="page">

    @if($membrete)
        <img class="bg" src="{{ $membrete }}">
    @else
        <div class="fallback-top"></div>
        <div class="fallback-bot"></div>
        <div class="fallback-logo">CRUD AGENCY</div>
    @endif

    <div class="content">

        <div class="title">
            CERTIFICADO DE<br>PRÁCTICAS PRE-PROFESIONALES
        </div>

        <p>
            El que suscribe, Renzo Castillo, gerente general de
            CRUD AGENCY S.A.C.{{ isset($ruc) ? ' con R.U.C. N.º ' . $ruc : '' }},
            otorga el presente certificado de prácticas pre-profesionales a:
        </p>

        <div class="name">{{ $nombreCompleto }}</div>

        <p>
            Identificado con el <strong>D.N.I.º {{ $dni }}</strong>,
            estudiante de {{ $semestre }} semestre de la carrera de
            <strong>{{ $carrera }}</strong> en <strong>{{ $institucion }}</strong>,
            desempeñándose en el área de <strong>{{ $area }}</strong>
            desde el <strong>{{ $fechaInicio }}</strong> hasta el
            <strong>{{ $fechaFin }}</strong> bajo el cargo de
            <strong>{{ $cargo }}</strong>. Durante este periodo, ha cumplido una
            jornada de {{ $horasSemana }} horas semanales, acumulando un total de
            <strong>{{ $hora }} horas</strong>
            @if(count($funciones)) desempeñando las siguientes funciones: @else. @endif
        </p>

        @if(count($funciones))
            <ul>
                @foreach($funciones as $f)
                    <li>{{ $f }}</li>
                @endforeach
            </ul>
        @endif

        <p>
            Demostrando durante su permanencia responsabilidad, dedicación y
            honestidad en las labores que le fueron encomendadas.
        </p>

        <p>
            Se expide el presente certificado para los fines que la parte
            interesada considere conveniente.
        </p>

        <div class="sign">
            @if($firma)
                <img class="sign-img" src="{{ $firma }}">
            @endif
            <div class="sign-left">
                <div class="sign-name">Renzo Castillo</div>
                <div class="sign-role">Gerente General</div>
            </div>
            <div class="sign-right">
                Lima, {{ now()->locale('es')->translatedFormat('d \d\e F \d\e Y') }}
            </div>
        </div>

    </div>
</div>
</body>
</html>
