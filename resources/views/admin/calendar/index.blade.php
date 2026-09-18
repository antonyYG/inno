<x-admin-layout>

    @push('css')
        <link href='https://cdn.jsdelivr.net/npm/fullcalendar@7.1.0/skeleton.css' rel='stylesheet' />
        <link href='https://cdn.jsdelivr.net/npm/fullcalendar@7.1.0/themes/monarch/theme.css' rel='stylesheet' />
        <link href='https://cdn.jsdelivr.net/npm/fullcalendar@7.1.0/themes/monarch/palettes/purple.css' rel='stylesheet' />
    @endpush

    <div x-data="data()">
        <div x-ref="calendar"></div>
    </div>


    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@7.1.0/all/global.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@7.1.0/themes/monarch/global.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@7.1.0/locales/es/global.js"></script>
        <script>
            function data() {
                return {
                    init() {
                        var calendarEl = this.$refs.calendar;
                        var calendar = new FullCalendar.Calendar(calendarEl, {
                            locale: 'es',



                            headerToolbar: {
                                left: 'prev,next today',
                                center: 'title',
                                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                            },
                            initialView: "dayGridMonth",

                            slotMinTime: '07:00:00',
                            slotMaxTime: '19:00:00',

                        });
                        calendar.render();
                    }
                }
            }
        </script>
    @endpush

</x-admin-layout>
