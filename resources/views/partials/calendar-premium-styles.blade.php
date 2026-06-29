<style>
    #dashboardCalendar.cal-grid-labels,
    #calendar.cal-grid-labels,
    #dashboardCalendar.cal-grid-minimal {
        background: linear-gradient(145deg, rgba(255,255,255,0.92), rgba(248,250,252,0.85));
        border-radius: 16px;
        padding: 12px;
        border: 1px solid rgba(148, 163, 184, 0.25);
    }
    #dashboardCalendar .fc, #calendar.cal-grid-labels .fc { color: #374151; }
    #dashboardCalendar .fc-theme-standard .fc-scrollgrid,
    #calendar.cal-grid-labels .fc-theme-standard .fc-scrollgrid { border: none !important; }
    #dashboardCalendar .fc-theme-standard td,
    #dashboardCalendar .fc-theme-standard th,
    #calendar.cal-grid-labels .fc-theme-standard td,
    #calendar.cal-grid-labels .fc-theme-standard th { border: none !important; }
    #dashboardCalendar .fc-col-header-cell,
    #calendar.cal-grid-labels .fc-col-header-cell {
        background: #f3f4f6 !important;
        padding: 8px 4px 12px !important;
    }
    #dashboardCalendar .fc-col-header-cell-cushion,
    #calendar.cal-grid-labels .fc-col-header-cell-cushion {
        font-size: 0.8rem !important;
        font-weight: 700 !important;
        text-transform: capitalize;
    }
    #dashboardCalendar .fc-col-header-cell:nth-child(1) .fc-col-header-cell-cushion,
    #calendar.cal-grid-labels .fc-col-header-cell:nth-child(1) .fc-col-header-cell-cushion { color: #be123c; }
    #dashboardCalendar .fc-col-header-cell:nth-child(2) .fc-col-header-cell-cushion,
    #calendar.cal-grid-labels .fc-col-header-cell:nth-child(2) .fc-col-header-cell-cushion { color: #c2410c; }
    #dashboardCalendar .fc-col-header-cell:nth-child(3) .fc-col-header-cell-cushion,
    #calendar.cal-grid-labels .fc-col-header-cell:nth-child(3) .fc-col-header-cell-cushion { color: #a16207; }
    #dashboardCalendar .fc-col-header-cell:nth-child(4) .fc-col-header-cell-cushion,
    #calendar.cal-grid-labels .fc-col-header-cell:nth-child(4) .fc-col-header-cell-cushion { color: #15803d; }
    #dashboardCalendar .fc-col-header-cell:nth-child(5) .fc-col-header-cell-cushion,
    #calendar.cal-grid-labels .fc-col-header-cell:nth-child(5) .fc-col-header-cell-cushion { color: #0369a1; }
    #dashboardCalendar .fc-col-header-cell:nth-child(6) .fc-col-header-cell-cushion,
    #calendar.cal-grid-labels .fc-col-header-cell:nth-child(6) .fc-col-header-cell-cushion { color: #4338ca; }
    #dashboardCalendar .fc-col-header-cell:nth-child(7) .fc-col-header-cell-cushion,
    #calendar.cal-grid-labels .fc-col-header-cell:nth-child(7) .fc-col-header-cell-cushion { color: #7e22ce; }
    #dashboardCalendar .fc-daygrid-body,
    #calendar.cal-grid-labels .fc-daygrid-body { background: #f3f4f6; }
    #dashboardCalendar .fc-daygrid-day,
    #calendar.cal-grid-labels .fc-daygrid-day {
        background: transparent !important;
        padding: 4px !important;
    }
    #dashboardCalendar .fc-daygrid-day-frame,
    #calendar.cal-grid-labels .fc-daygrid-day-frame {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        min-height: 96px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        transition: box-shadow 0.15s, border-color 0.15s;
    }
    #dashboardCalendar .fc-day-mon .fc-daygrid-day-frame,
    #calendar.cal-grid-labels .fc-day-mon .fc-daygrid-day-frame { background: linear-gradient(180deg, #fff1f2, #fff); }
    #dashboardCalendar .fc-day-tue .fc-daygrid-day-frame,
    #calendar.cal-grid-labels .fc-day-tue .fc-daygrid-day-frame { background: linear-gradient(180deg, #fff7ed, #fff); }
    #dashboardCalendar .fc-day-wed .fc-daygrid-day-frame,
    #calendar.cal-grid-labels .fc-day-wed .fc-daygrid-day-frame { background: linear-gradient(180deg, #fefce8, #fff); }
    #dashboardCalendar .fc-day-thu .fc-daygrid-day-frame,
    #calendar.cal-grid-labels .fc-day-thu .fc-daygrid-day-frame { background: linear-gradient(180deg, #f0fdf4, #fff); }
    #dashboardCalendar .fc-day-fri .fc-daygrid-day-frame,
    #calendar.cal-grid-labels .fc-day-fri .fc-daygrid-day-frame { background: linear-gradient(180deg, #f0f9ff, #fff); }
    #dashboardCalendar .fc-day-sat .fc-daygrid-day-frame,
    #calendar.cal-grid-labels .fc-day-sat .fc-daygrid-day-frame { background: linear-gradient(180deg, #eef2ff, #fff); }
    #dashboardCalendar .fc-day-sun .fc-daygrid-day-frame,
    #calendar.cal-grid-labels .fc-day-sun .fc-daygrid-day-frame { background: linear-gradient(180deg, #faf5ff, #fff); }
    #dashboardCalendar .fc-daygrid-day:hover .fc-daygrid-day-frame,
    #calendar.cal-grid-labels .fc-daygrid-day:hover .fc-daygrid-day-frame {
        border-color: #c7d2fe;
        box-shadow: 0 2px 8px rgba(79, 70, 229, 0.08);
    }
    #dashboardCalendar .fc-day-today .fc-daygrid-day-frame,
    #calendar.cal-grid-labels .fc-day-today .fc-daygrid-day-frame {
        border-color: #6366f1 !important;
        box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.28), 0 8px 18px rgba(99, 102, 241, 0.12);
        background: linear-gradient(180deg, #eef2ff, #fff) !important;
    }
    #dashboardCalendar .fc-daygrid-day-number,
    #calendar.cal-grid-labels .fc-daygrid-day-number {
        color: #374151 !important;
        font-size: 0.875rem !important;
        font-weight: 600 !important;
    }
    #dashboardCalendar .fc-toolbar-title,
    #calendar.cal-grid-labels .fc-toolbar-title {
        font-size: 1rem !important;
        font-weight: 800 !important;
        color: #1e293b;
    }
    #dashboardCalendar .fc-button-primary,
    #calendar.cal-grid-labels .fc-button-primary {
        background: linear-gradient(135deg, #2563eb, #0d9488) !important;
        border: none !important;
        font-weight: 600 !important;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
    }
</style>
