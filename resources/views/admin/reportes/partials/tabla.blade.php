{{-- TABLA UNIFICADA --}}
<div class="space-y-4">

    <div class="overflow-x-auto rounded-lg border border-slate-200">

        {{-- Asistencias y evidencias --}}
        <template x-if="filtros.tipoReporte !== 'auditorias'">
            <table class="w-full min-w-[760px] text-left text-sm">

                <thead class="border-b border-slate-200 bg-slate-100 text-xs uppercase tracking-wider text-slate-600">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Practicante</th>
                        <th class="px-5 py-3 font-semibold">Área</th>
                        <th class="px-5 py-3 font-semibold">Fecha</th>
                        <th class="px-5 py-3 font-semibold"
                            x-text="filtros.tipoReporte === 'evidencias' ? 'Descripción' : 'Estado'">
                        </th>
                        <template x-if="filtros.tipoReporte === 'evidencias'">
                            <th class="px-5 py-3 font-semibold">Estado</th>
                        </template>
                        <template x-if="filtros.tipoReporte === 'evidencias'">
                            <th class="px-5 py-3 font-semibold text-center">Archivo</th>
                        </template>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <template x-for="item in resultados" :key="item.id">
                        <tr class="transition-colors hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-3 font-medium text-slate-800"
                                x-text="item.practicante">
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-600"
                                x-text="item.area">
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-600"
                                x-text="item.fecha">
                            </td>

                            <td class="px-5 py-3 text-slate-600">
                                <template x-if="filtros.tipoReporte === 'asistencias'">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                                        :class="estadoClase(item.estado)"
                                        x-text="estadoTexto(item.estado)">
                                    </span>
                                </template>

                                <template x-if="filtros.tipoReporte === 'evidencias'">
                                    <span
                                        class="block max-w-xs truncate"
                                        :title="item.descripcion"
                                        x-text="item.descripcion">
                                    </span>
                                </template>
                            </td>

                            <template x-if="filtros.tipoReporte === 'evidencias'">
                                <td class="px-5 py-3">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                                        :class="estadoClase(item.estado)"
                                        x-text="estadoTexto(item.estado)">
                                    </span>
                                </td>
                            </template>

                            <template x-if="filtros.tipoReporte === 'evidencias'">
                                <td class="px-5 py-3 text-center">
                                    <button
                                        type="button"
                                        @click="abrirEvidencia(item)"
                                        :disabled="!item.archivo"
                                        class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-medium text-indigo-600 hover:bg-indigo-50 disabled:cursor-not-allowed disabled:opacity-40"
                                    >
                                        <i class="fa-solid fa-eye"></i>
                                        Visualizar
                                    </button>
                                </td>
                            </template>
                        </tr>
                    </template>
                </tbody>

            </table>
        </template>

        {{-- Auditoría general --}}
        <template x-if="filtros.tipoReporte === 'auditorias'">
            <table class="w-full min-w-[1200px] text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-100 text-xs uppercase tracking-wider text-slate-600">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Fecha de revisión</th>
                        <th class="px-5 py-3 font-semibold">Practicante</th>
                        <th class="px-5 py-3 font-semibold">Área</th>
                        <th class="px-5 py-3 font-semibold">Detalle</th>
                        <th class="px-5 py-3 font-semibold">Fecha de asistencia</th>
                        <th class="px-5 py-3 font-semibold">Administrador</th>
                        <th class="px-5 py-3 font-semibold">Acción</th>
                        <th class="px-5 py-3 font-semibold">Observación</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <template x-for="item in resultados" :key="item.id">
                        <tr class="transition-colors hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-3 text-slate-600" x-text="item.fecha_revision"></td>
                            <td class="whitespace-nowrap px-5 py-3 font-medium text-slate-800" x-text="item.practicante"></td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-600" x-text="item.area"></td>
                            <td class="max-w-xs px-5 py-3 text-slate-600">
                                <span class="block truncate" :title="item.detalle" x-text="item.detalle"></span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-600" x-text="item.fecha_asistencia"></td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-600" x-text="item.administrador"></td>
                            <td class="whitespace-nowrap px-5 py-3">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                                    :class="estadoClase(item.accion)"
                                    x-text="estadoTexto(item.accion)">
                                </span>
                            </td>
                            <td class="max-w-xs px-5 py-3 text-slate-600">
                                <span class="block truncate" :title="item.observacion" x-text="item.observacion"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </template>

    </div>

    @include('admin.reportes.partials.paginacion')
</div>
