<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Análisis del proceso - Caso 2</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>

<body>
    <header class="topbar">
        <div><span class="eyebrow">CASO 2 · ANÁLISIS DEL PROCESO</span>
            <h1>Diagnóstico: 500 correos diarios</h1>
            <p>AS-IS, cuello de botella y rediseño TO-BE antes de automatizar.</p>
        </div>
        <div class="technology">PHP + HTML + CSS</div>
    </header>
    <nav class="subnav">
        <a href="analisis.php" class="active">Análisis del proceso</a>
        <a href="index.php">Bandeja en vivo (demo funcional) →</a>
    </nav>
    <main class="container">

        <section class="panel">
            <div class="panel-title">
                <div><span class="eyebrow">DIAGNÓSTICO · PROCESO ACTUAL</span>
                    <h2>Diagrama AS-IS con tiempos estimados</h2>
                </div>
            </div>
            <p class="lead">500 correos electrónicos diarios, revisados a mano por el equipo de atención. Solicitudes de clientes, reclamos, consultas técnicas, comerciales y de soporte operativo entran todos por la misma bandeja, sin distinguir urgencia ni destinatario.</p>
            <img src="img/as-is.png" alt="Diagrama AS-IS del proceso de correos" class="diagram">
        </section>

        <section class="panel">
            <div class="panel-title">
                <div><span class="eyebrow">MEDICIÓN</span>
                    <h2>Dónde está el cuello de botella</h2>
                </div>
            </div>
            <div class="metrics metrics-4">
                <article class="metric"><span>Revisión de bandeja</span><strong>1.000 min</strong><small>min/día · 2,0 min/caso</small></article>
                <article class="metric"><span>Clasificación manual</span><strong>750 min</strong><small>min/día · 1,5 min/caso</small></article>
                <article class="metric"><span>Reenvío al área</span><strong>250 min</strong><small>min/día · 0,5 min/caso</small></article>
                <article class="metric highlight"><span>El área responde</span><strong>5.000 min</strong><small>min/día · 71 % del total</small></article>
            </div>
            <img src="img/cuello-de-botella.png" alt="Carga diaria por etapa del proceso" class="diagram">
            <p class="lead">El área responde concentra 5.000 de los 7.000 minutos diarios registrados (71 %), pero automatizar esa entrega final no depende del equipo de bandeja. Revisión y el bloque clasificación + reenvío cargan lo mismo en minutos (1.000 min/día cada uno), pero solo el segundo exige criterio humano y no escala sin entrenamiento: <strong>ese es el cuello de botella real</strong>, con 16,7 horas-persona/día concentradas ahí.</p>
        </section>

        <section class="panel">
            <div class="panel-title">
                <div><span class="eyebrow">SOLUCIÓN PROPUESTA</span>
                    <h2>Diagrama TO-BE — automatizando el cuello de botella</h2>
                </div>
            </div>
            <img src="img/To-Be.png" alt="Diagrama TO-BE del proceso automatizado" class="diagram">
            <div class="rules">
                <div class="rule-grid">
                    <div><b>Simplificar</b>
                        <p>El correo se convierte en una entrada estructurada: id, cliente, asunto, mensaje y hora. Se elimina la revisión manual campo por campo.</p>
                    </div>
                    <div><b>Integrar</b>
                        <p>Un motor de reglas evalúa si la solicitud coincide con alguna categoría conocida (reclamo, consulta técnica, comercial, soporte operativo o solicitud de cliente).</p>
                    </div>
                    <div><b>Automatizar</b>
                        <p>Si coincide, se asigna área responsable y prioridad (Crítica / Alta / Media / Baja) sin intervención humana, y pasa directo al área responsable.</p>
                    </div>
                    <div><b>Control de riesgo</b>
                        <p>Si no coincide con ninguna regla, el caso no se pierde: queda marcado como "Requiere revisión", con prioridad media, para que el equipo de bandeja lo revise manualmente.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-title">
                <div><span class="eyebrow">RIESGOS Y LÍMITES</span>
                    <h2>Qué pasa cuando el clasificador se equivoca</h2>
                </div>
            </div>
            <p class="lead">El clasificador es un motor de reglas por palabras clave: rápido y transparente, pero limitado. No entiende sinónimos, redacciones nuevas ni errores de escritura, y solo reconoce lo que alguien anticipó al escribir la regla. Por eso el diseño no deja que el sistema "adivine" cuando no está seguro.</p>
            <div class="risk-example">
                <b>Ejemplo real en los datos de prueba:</b> REQ-002 ("No puedo ingresar al portal") y REQ-011 ("Consulta de funcionamiento") no contienen ninguna de las palabras clave configuradas. Antes, el sistema los clasificaba en silencio como "Solicitud de cliente" — una categoría por defecto que podía ocultar un caso mal atendido. Ahora quedan marcados con el estado <span class="pill review">Revisión</span> en la bandeja, visibles en la métrica "Revisión manual" del panel superior, para que una persona los revise antes de cerrarlos.
            </div>
            <table class="risk-table">
                <thead>
                    <tr>
                        <th>Riesgo</th>
                        <th>Cuándo ocurre</th>
                        <th>Mitigación implementada</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><b>Falso negativo</b></td>
                        <td>Una solicitud urgente no usa ninguna de las palabras de la lista de urgencia (p. ej. dice "no responde" en vez de "caído").</td>
                        <td>No se le asigna Crítica automáticamente, pero tampoco se cierra sola: si no coincide con ninguna regla de categoría, cae en "Revisión manual" con prioridad Media como mínimo.</td>
                    </tr>
                    <tr>
                        <td><b>Falso positivo</b></td>
                        <td>Una palabra clave aparece fuera de contexto (p. ej. "operación" en un mensaje que no es de soporte operativo).</td>
                        <td>El equipo del área responsable revisa el caso al atenderlo; la clasificación automática es una prioridad sugerida, no una decisión final e irreversible.</td>
                    </tr>
                    <tr>
                        <td><b>Ambigüedad entre reglas</b></td>
                        <td>Un mensaje coincide con palabras de más de una categoría (p. ej. menciona "reclamo" y "cotización" a la vez).</td>
                        <td>Se usa la primera coincidencia en un orden fijo de prioridad (Reclamo antes que Comercial), una decisión de diseño explícita y documentada, no accidental.</td>
                    </tr>
                    <tr>
                        <td><b>Alcance limitado del lenguaje</b></td>
                        <td>Sinónimos, errores ortográficos, abreviaturas o mensajes en otro idioma que el motor no reconoce.</td>
                        <td>El caso no se descarta: al no matchear ninguna regla, se enruta igual a revisión manual en vez de perderse o asignarse mal.</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <div class="cta"><a class="button primary" href="index.php">Ver la bandeja funcionando →</a></div>
    </main>
    <footer>Prototipo académico · Datos simulados · Caso 2 - Sesión 2 BPM · Diagnóstico y rediseño del proceso.</footer>
</body>

</html>
