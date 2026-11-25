import { Head, router, useForm } from '@inertiajs/react';
import {
    Activity,
    ArrowDownToLine,
    BrainCircuit,
    Check,
    ClipboardList,
    FileText,
    Gauge,
    Layers,
    LoaderCircle,
    Mail,
    Play,
    Radio,
    Search,
    ShieldCheck,
    SlidersHorizontal,
    Timer,
    Wallet,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import './centro-tqa.css';

type Suite = {
    id: string;
    tipo: string;
    nombre: string;
    descripcion: string;
    casos: { id: string; descripcion: string }[];
};
type Caso = {
    id: string;
    nombre: string;
    estado: string;
    duracion: string;
    observacion: string;
    comprobacion?: string;
    aserciones?: number;
    error?: string | null;
};
type Corrida = {
    id: string;
    suite: string;
    tipo: string;
    inicio: string;
    fin: string;
    duracion: string;
    estado: string;
    correo: string;
    correo_estado: string;
    error_proceso?: string;
    resumen: {
        total: number;
        pass: number;
        fail: number;
        skipped: number;
        exito: number;
    };
    casos: Caso[];
    reportes: { pdf: string; html: string };
};
type Props = {
    suites: Suite[];
    ultima: Corrida | null;
};
const iconos: Record<string, typeof Activity> = {
    smoke: ShieldCheck,
    finanzas: Wallet,
    integracion: Radio,
    motor: BrainCircuit,
    estabilidad: SlidersHorizontal,
    rendimiento: Gauge,
    regresion: Layers,
};
const estadosCorreo: Record<string, string> = {
    enviado: 'Enviado',
    registrado: 'Registrado en el correo local',
    fallido: 'Falló el envío',
    pendiente: 'Pendiente de envío',
};

function nombreCaso(nombre: string) {
    return (nombre.split(' :: ').pop() || nombre)
        .replace(/^test_/, '')
        .replaceAll('_', ' ');
}

function Resultados({ corrida }: { corrida: Corrida }) {
    const [estado, setEstado] = useState('');
    const [busqueda, setBusqueda] = useState('');
    const consulta = busqueda.trim().toLocaleLowerCase('es');
    const casos = corrida.casos.filter(
        (c) =>
            (!estado || c.estado === estado) &&
            [
                c.id,
                c.nombre,
                nombreCaso(c.nombre),
                c.comprobacion,
                c.observacion,
                c.error,
            ]
                .join(' ')
                .toLocaleLowerCase('es')
                .includes(consulta),
    );
    const { resumen } = corrida;

    return (
        <section className="qa-panel">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p className="qa-label">Última ejecución</p>
                    <h2 className="mt-1 text-2xl font-bold">{corrida.suite}</h2>
                </div>
                <span
                    className="qa-pill"
                    data-tone={
                        corrida.estado === 'finalizada' ? 'pass' : 'fail'
                    }
                >
                    {corrida.estado === 'finalizada'
                        ? 'Finalizada'
                        : 'Con fallos'}
                </span>
            </div>
            <dl className="qa-meta mt-4">
                {[
                    ['Inicio', corrida.inicio],
                    ['Fin', corrida.fin],
                    ['Duración', corrida.duracion],
                ].map(([k, v]) => (
                    <div key={k}>
                        <dt>{k}</dt>
                        <dd>{v}</dd>
                    </div>
                ))}
            </dl>
            {corrida.error_proceso && (
                <p role="alert" className="qa-error">
                    {corrida.error_proceso}
                </p>
            )}
            <div className="mt-5 grid grid-cols-2 gap-2 md:grid-cols-4">
                {[
                    [resumen.total, 'Casos ejecutados', ''],
                    [resumen.pass, 'Aprobados', 'pass'],
                    [resumen.fail, 'Fallidos', 'fail'],
                    [resumen.skipped, 'Omitidos', 'skip'],
                ].map(([n, label, tone]) => (
                    <div className="qa-stat" key={label}>
                        <strong data-tone={tone}>{n}</strong>
                        <span>{label}</span>
                    </div>
                ))}
            </div>
            {resumen.total > 0 && (
                <div className="mt-3">
                    <div className="qa-track" aria-hidden="true">
                        <i
                            style={{
                                width:
                                    (100 * resumen.pass) / resumen.total + '%',
                            }}
                        />
                        <i
                            style={{
                                width:
                                    (100 * resumen.fail) / resumen.total + '%',
                            }}
                        />
                    </div>
                    <p className="qa-muted mt-1 text-xs">
                        {resumen.exito}% de casos aprobados
                    </p>
                </div>
            )}
            <div className="mt-6 flex flex-wrap items-end justify-between gap-3">
                <h3 className="text-lg font-bold">Detalle de casos</h3>
                <div className="flex max-w-full flex-wrap gap-2">
                    <label className="qa-input flex items-center gap-2">
                        <Search size={16} />
                        <input
                            type="search"
                            aria-label="Buscar caso u observación"
                            value={busqueda}
                            onChange={(e) => setBusqueda(e.target.value)}
                            placeholder="Buscar caso u observación"
                            className="min-w-0 flex-1"
                        />
                    </label>
                    <select
                        aria-label="Filtrar casos por estado"
                        className="qa-input"
                        value={estado}
                        onChange={(e) => setEstado(e.target.value)}
                    >
                        <option value="">Todos los estados</option>
                        {Array.from(
                            new Set(corrida.casos.map((c) => c.estado)),
                        ).map((e) => (
                            <option key={e}>{e}</option>
                        ))}
                    </select>
                </div>
            </div>
            <div className="qa-list mt-3">
                {casos.map((c, i) => (
                    <details key={c.id + '-' + i} className="qa-case">
                        <summary>
                            <span className="qa-case-id">{c.id}</span>
                            <span className="min-w-0 flex-1 font-semibold break-words first-letter:uppercase">
                                {nombreCaso(c.nombre)}
                            </span>
                            <span
                                className="qa-pill"
                                data-tone={
                                    c.estado === 'PASS'
                                        ? 'pass'
                                        : c.estado === 'FAIL'
                                          ? 'fail'
                                          : 'skip'
                                }
                            >
                                {c.estado}
                            </span>
                            <span className="qa-detail-toggle">
                                <span>Ver detalle</span>
                                <span>Ocultar detalle</span>
                            </span>
                        </summary>
                        <div className="qa-detail">
                            <p className="qa-label">Comprobación del caso</p>
                            <p className="mt-1 mb-2">
                                {c.comprobacion ?? nombreCaso(c.nombre)}
                            </p>
                            <span className="inline-flex items-center gap-1">
                                <Timer size={14} />
                                {c.duracion}
                                {c.aserciones !== undefined &&
                                    ` · ${c.aserciones} aserciones registradas`}
                            </span>
                            {c.estado === 'FAIL' ? (
                                <div className="qa-error">
                                    <strong>
                                        Error registrado por PHPUnit
                                    </strong>
                                    <pre className="mt-1 break-all whitespace-pre-wrap">
                                        {c.error || c.observacion}
                                    </pre>
                                </div>
                            ) : (
                                <p className="mt-1 break-words whitespace-pre-wrap">
                                    {c.estado === 'PASS'
                                        ? 'La prueba finalizó correctamente.'
                                        : c.observacion}
                                </p>
                            )}
                            <code className="mt-2 block text-xs break-all">
                                {c.nombre}
                            </code>
                        </div>
                    </details>
                ))}
                {!casos.length && (
                    <p className="qa-muted p-4 text-sm">
                        {corrida.casos.length
                            ? 'Ningún caso coincide con los filtros.'
                            : 'La ejecución no registró casos.'}
                    </p>
                )}
            </div>
            <footer className="qa-footer mt-5 flex flex-wrap items-center justify-between gap-4 pt-4">
                <div className="min-w-0">
                    <p className="qa-label">Entrega de informes</p>
                    <p className="mt-1 text-sm break-all">
                        {corrida.correo} ·{' '}
                        {estadosCorreo[corrida.correo_estado] ??
                            corrida.correo_estado}
                    </p>
                </div>
                <div className="flex flex-wrap gap-2">
                    <a className="qa-download" href={corrida.reportes.pdf}>
                        <ArrowDownToLine size={15} />
                        Informe PDF
                    </a>
                    <a className="qa-download" href={corrida.reportes.html}>
                        <ArrowDownToLine size={15} />
                        Resumen HTML
                    </a>
                </div>
            </footer>
        </section>
    );
}

export default function CentroTqa({ suites, ultima }: Props) {
    const form = useForm({
        suite: suites[0].id,
        correo: '',
        casos: suites[0].casos.map((c) => c.id),
    });
    const seleccion = suites.find((s) => s.id === form.data.suite) ?? suites[0];
    const tipos = [...new Set(suites.map((s) => s.tipo))];
    const cantidad = form.data.casos.length;
    const errorSeleccion = Object.entries(form.errors).find(
        ([campo]) => campo === 'casos' || campo.startsWith('casos.'),
    )?.[1];
    function elegirSuite(suite: Suite) {
        if (suite.id === seleccion.id) {
return;
}

        form.clearErrors();
        form.setData({
            ...form.data,
            suite: suite.id,
            casos: suite.casos.map((c) => c.id),
        });
    }
    function ejecutar(e: FormEvent) {
        e.preventDefault();

        if (!cantidad || form.processing) {
return;
}

        form.post('/ejecutar', { preserveScroll: true });
    }

    return (
        <main className="px-4 py-6 sm:px-8">
            <Head title="Centro TQA" />
            <div className="qa-center mx-auto max-w-6xl space-y-5">
                <nav className="qa-muted flex items-center justify-between text-sm"><strong>TQA Integrador</strong>
                    <button className="qa-tab" type="button" onClick={() => router.post('/salir')}>Cerrar sesión</button>
                </nav>
                <header className="qa-hero">
                    <div>
                        <p className="inline-flex items-center gap-2 text-xs font-semibold tracking-widest text-cyan-200 uppercase">
                            <Activity size={17} />
                            Centro TQA
                        </p>
                        <h1 className="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">
                            Pruebas automatizadas
                        </h1>
                        <p className="mt-2 max-w-xl text-sm leading-relaxed text-slate-200">
                            Selecciona qué parte del sistema de transporte quieres verificar y
                            revisa el resultado de cada prueba.
                        </p>
                    </div>
                    <dl className="qa-hero-stats">
                        <div>
                            <dd>{suites.length}</dd>
                            <dt>Suites</dt>
                        </div>
                        <div>
                            <dd>{tipos.length}</dd>
                            <dt>Tipos de prueba</dt>
                        </div>
                    </dl>
                </header>
                <form
                    onSubmit={ejecutar}
                    className="grid items-start gap-5 lg:grid-cols-[minmax(0,1.65fr)_minmax(17rem,1fr)]"
                >
                    <section className="qa-panel">
                        <h2 className="qa-heading">
                            <Layers size={20} />
                            Catálogo de pruebas
                        </h2>
                        <fieldset
                            disabled={form.processing}
                            className="mt-4 flex flex-wrap gap-2"
                        >
                            <legend className="sr-only">Tipo de prueba</legend>
                            {tipos.map((t) => (
                                <button
                                    type="button"
                                    className="qa-tab"
                                    aria-pressed={seleccion.tipo === t}
                                    key={t}
                                    onClick={() =>
                                        seleccion.tipo !== t &&
                                        elegirSuite(
                                            suites.find((s) => s.tipo === t)!,
                                        )
                                    }
                                >
                                    {t}
                                </button>
                            ))}
                        </fieldset>
                        <fieldset
                            disabled={form.processing}
                            className="mt-5 grid gap-3"
                        >
                            <legend className="sr-only">
                                Suite a ejecutar
                            </legend>
                            {suites
                                .filter((s) => s.tipo === seleccion.tipo)
                                .map((s) => {
                                    const Icon = iconos[s.id] ?? ClipboardList;
                                    const activa = s.id === seleccion.id;

                                    return (
                                        <label
                                            key={s.id}
                                            className="qa-card"
                                            data-active={activa}
                                        >
                                            <input
                                                className="sr-only"
                                                type="radio"
                                                name="suite"
                                                checked={activa}
                                                onChange={() => elegirSuite(s)}
                                            />
                                            <span className="qa-icon">
                                                <Icon size={21} />
                                            </span>
                                            <span className="min-w-0 flex-1">
                                                <strong className="block">
                                                    {s.nombre}
                                                </strong>
                                                <span className="qa-muted mt-1 block text-sm leading-relaxed">
                                                    {s.descripcion}
                                                </span>
                                            </span>
                                            <span
                                                className="qa-radio"
                                                aria-hidden="true"
                                            >
                                                {activa && <Check size={12} />}
                                            </span>
                                        </label>
                                    );
                                })}
                        </fieldset>
                        <fieldset
                            className="qa-checklist mt-6"
                            disabled={form.processing}
                        >
                            <legend className="text-lg font-bold">
                                Casos que se ejecutarán
                            </legend>
                            <div className="my-3 flex flex-wrap items-center justify-between gap-2">
                                <span
                                    className="qa-muted text-sm"
                                    aria-live="polite"
                                >
                                    {cantidad} de {seleccion.casos.length}{' '}
                                    seleccionados
                                </span>
                                <button
                                    type="button"
                                    className="qa-tab"
                                    onClick={() =>
                                        form.setData(
                                            'casos',
                                            cantidad === seleccion.casos.length
                                                ? []
                                                : seleccion.casos.map(
                                                      (c) => c.id,
                                                  ),
                                        )
                                    }
                                >
                                    {cantidad === seleccion.casos.length
                                        ? 'Desmarcar todos'
                                        : 'Marcar todos'}
                                </button>
                            </div>
                            <div className="qa-check-items">
                                {seleccion.casos.map((c) => (
                                    <label key={c.id}>
                                        <input
                                            type="checkbox"
                                            checked={form.data.casos.includes(
                                                c.id,
                                            )}
                                            onChange={(e) =>
                                                form.setData(
                                                    'casos',
                                                    e.target.checked
                                                        ? [
                                                              ...form.data
                                                                  .casos,
                                                              c.id,
                                                          ]
                                                        : form.data.casos.filter(
                                                              (id) =>
                                                                  id !== c.id,
                                                          ),
                                                )
                                            }
                                        />
                                        <span>{c.descripcion}</span>
                                    </label>
                                ))}
                            </div>
                        </fieldset>
                    </section>
                    <section className="qa-panel">
                        <h2 className="qa-heading">
                            <Play size={19} />
                            Preparar ejecución
                        </h2>
                        <p className="qa-label mt-5">{seleccion.tipo}</p>
                        <p className="mt-1 text-lg font-bold">
                            {seleccion.nombre}
                        </p>
                        <p className="qa-muted mt-2 text-sm">
                            {cantidad
                                ? `${cantidad} de ${seleccion.casos.length} casos seleccionados`
                                : 'Marca al menos un caso para ejecutar.'}
                        </p>
                        <label
                            className="mt-5 block text-sm font-semibold"
                            htmlFor="qa-correo"
                        >
                            Correo para los informes
                        </label>
                        <div className="qa-input mt-2 flex items-center gap-2">
                            <Mail size={16} />
                            <input
                                id="qa-correo"
                                type="email"
                                required
                                maxLength={255}
                                placeholder="qa@ejemplo.com"
                                autoComplete="email"
                                value={form.data.correo}
                                disabled={form.processing}
                                onChange={(e) =>
                                    form.setData('correo', e.target.value)
                                }
                                className="min-w-0 flex-1"
                            />
                        </div>
                        <p className="qa-muted mt-2 text-xs">
                            Adjuntos de la ejecución: informe PDF y resumen
                            HTML.
                        </p>
                        {(form.errors.correo ||
                            form.errors.suite ||
                            errorSeleccion) && (
                            <p className="qa-error" role="alert">
                                {form.errors.correo ??
                                    form.errors.suite ??
                                    errorSeleccion}
                            </p>
                        )}
                        <button
                            className="qa-start mt-5"
                            disabled={form.processing || !cantidad}
                        >
                            {form.processing ? (
                                <LoaderCircle
                                    size={17}
                                    className="motion-safe:animate-spin"
                                />
                            ) : (
                                <Play size={17} />
                            )}{' '}
                            {form.processing
                                ? 'Ejecutando casos…'
                                : 'Ejecutar suite'}
                        </button>
                        {form.processing && (
                            <p className="qa-muted mt-2 text-xs" role="status">
                                Al finalizar verás los resultados debajo.
                            </p>
                        )}
                    </section>
                </form>
                {ultima ? (
                    <Resultados key={ultima.id} corrida={ultima} />
                ) : (
                    <section className="qa-panel flex items-center gap-4">
                        <span className="qa-icon">
                            <FileText size={23} />
                        </span>
                        <div>
                            <h2 className="font-bold">
                                Resultados de la ejecución
                            </h2>
                            <p className="qa-muted mt-1 text-sm">
                                Ejecuta una suite para ver sus casos,
                                observaciones y archivos de descarga.
                            </p>
                        </div>
                    </section>
                )}
            </div>
        </main>
    );
}
