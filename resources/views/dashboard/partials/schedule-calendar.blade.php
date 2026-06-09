<section id="dashboard-schedule" class="glass-card p-6 mt-2" style="min-height: 600px;" data-demo-tour="schedule-calendar-wrap">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4">
        <div>
            <p class="glass-section-title mb-0">📅 Schedule & Deadlines</p>
            <p class="mt-1 text-xs text-gray-500">Colored dots = tasks &amp; dues. Click a dot for details, drag to reschedule, or click a day to add a task.</p>
        </div>
        <div class="flex gap-3 text-xs text-gray-500 flex-wrap">
            <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-blue-400"></span> Tasks</span>
            <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-violet-400"></span> Dues</span>
            <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-emerald-400"></span> Done</span>
            <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-rose-400"></span> Overdue</span>
        </div>
    </div>
    @include('dashboard.partials.calendar-filters')
    <div id="dashboardCalendar" class="cal-grid-minimal" data-demo-tour="schedule-calendar" style="min-height: 520px;"></div>
</section>
