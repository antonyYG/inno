{{-- FILTROS ESPECÍFICOS POR TIPO DE REPORTE --}}
<div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">

    <div>
        <h2 class="font-semibold text-slate-800">
            Filtros de búsqueda
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Los filtros se aplican en la base de datos y se conservan al paginar o exportar.
        </p>
    </div>

    <div class="max-w-sm">
        <x-select
            name="tipoReporte"
            label="Tipo de reporte"
            x-model="filtros.tipoReporte"
            @change="cambiarTipoReporte()"
        >
            <option value="asistencias">Asistencias</option>
            <option value="evidencias">Evidencias</option>
            <option value="auditorias">Auditoría general</option>
        </x-select>
    </div>

    <div class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Practicante</label>

            <x-select-search
                name="practicante"
                :options="$practicantes"
                x-model="filtros.practicante"
                placeholder="Todos los practicantes"
            />
        </div>

        <template x-if="filtros.tipoReporte === 'auditorias'">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Administrador</label>

                <x-select-search
                    name="administrador"
                    :options="$administradores"
                    x-model="filtros.administrador"
                    placeholder="Todos los administradores"
                />
            </div>
        </template>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Fecha inicial</label>

            <x-input-fecha
                name="fechaDesde"
                x-model="filtros.fechaDesde"
            />
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Fecha final</label>

            <x-input-fecha
                name="fechaHasta"
                x-model="filtros.fechaHasta"
            />
        </div>

        <div>
            <label
                class="mb-1 block text-sm font-medium text-slate-700"
                x-text="etiquetaEstado()"
            >Estado</label>

            <select
                name="estadoAsistencia"
                x-model="filtros.estadoAsistencia"
                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
            >
                <option value="" x-text="placeholderEstado()">Todos los estados</option>

                <template x-if="filtros.tipoReporte === 'asistencias'">
                    <optgroup label="Estados de asistencia">
                        <option value="asistio">Asistió</option>
                        <option value="tardanza">Tardanza</option>
                        <option value="falto">Falta</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="justificado">Justificado</option>
                    </optgroup>
                </template>

                <template x-if="filtros.tipoReporte === 'evidencias'">
                    <optgroup label="Estados de evidencia">
                        <option value="pendiente">Pendiente</option>
                        <option value="aprobada">Aprobada</option>
                        <option value="rechazada">Rechazada</option>
                    </optgroup>
                </template>

                <template x-if="filtros.tipoReporte === 'auditorias'">
                    <optgroup label="Acciones de auditoría">
                        <option value="aceptada">Aceptada / aprobada</option>
                        <option value="rechazada">Rechazada</option>
                        <option value="actividad">Actividad registrada</option>
                    </optgroup>
                </template>
            </select>
        </div>

        <div class="flex gap-3 sm:col-span-2 xl:col-span-1">
            <button
                type="button"
                @click="buscar()"
                :disabled="cargando"
                class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700 disabled:opacity-50"
            >
                <i class="fa-solid fa-magnifying-glass text-xs"></i>

                <span x-show="!cargando">Buscar</span>
                <span x-show="cargando" x-cloak>Buscando...</span>
            </button>

            <button
                type="button"
                @click="limpiar()"
                :disabled="cargando"
                class="inline-flex items-center justify-center whitespace-nowrap rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:opacity-50"
            >
                <i class="fa-solid fa-rotate-left mr-2 text-xs"></i>
                Limpiar filtros
            </button>
        </div>

    </div>
</div>
