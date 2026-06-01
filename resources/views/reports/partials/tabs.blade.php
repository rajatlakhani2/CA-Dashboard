<div class="mb-6">
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('reports.financial', request()->query()) }}" class="px-5 py-2.5 text-base font-bold rounded-full transition-all duration-200 shadow-sm border {{ request()->routeIs('reports.financial') ? 'bg-indigo-600 text-white border-indigo-600 shadow-indigo-200 transform scale-105' : 'bg-white text-gray-500 border-gray-200 hover:border-indigo-300 hover:text-indigo-600 hover:shadow-md' }}">
            Financial
        </a>
        <a href="{{ route('reports.compliance', request()->query()) }}" class="px-5 py-2.5 text-base font-bold rounded-full transition-all duration-200 shadow-sm border {{ request()->routeIs('reports.compliance') ? 'bg-indigo-600 text-white border-indigo-600 shadow-indigo-200 transform scale-105' : 'bg-white text-gray-500 border-gray-200 hover:border-indigo-300 hover:text-indigo-600 hover:shadow-md' }}">
            Compliance
        </a>
        <a href="{{ route('reports.service', request()->query()) }}" class="px-5 py-2.5 text-base font-bold rounded-full transition-all duration-200 shadow-sm border {{ request()->routeIs('reports.service') ? 'bg-indigo-600 text-white border-indigo-600 shadow-indigo-200 transform scale-105' : 'bg-white text-gray-500 border-gray-200 hover:border-indigo-300 hover:text-indigo-600 hover:shadow-md' }}">
            Services
        </a>
        <a href="{{ route('reports.client', request()->query()) }}" class="px-5 py-2.5 text-base font-bold rounded-full transition-all duration-200 shadow-sm border {{ request()->routeIs('reports.client') ? 'bg-indigo-600 text-white border-indigo-600 shadow-indigo-200 transform scale-105' : 'bg-white text-gray-500 border-gray-200 hover:border-indigo-300 hover:text-indigo-600 hover:shadow-md' }}">
            Clients
        </a>
        <a href="{{ route('reports.task', request()->query()) }}" class="px-5 py-2.5 text-base font-bold rounded-full transition-all duration-200 shadow-sm border {{ request()->routeIs('reports.task') ? 'bg-indigo-600 text-white border-indigo-600 shadow-indigo-200 transform scale-105' : 'bg-white text-gray-500 border-gray-200 hover:border-indigo-300 hover:text-indigo-600 hover:shadow-md' }}">
            Tasks
        </a>
        <a href="{{ route('reports.due-date', request()->query()) }}" class="px-5 py-2.5 text-base font-bold rounded-full transition-all duration-200 shadow-sm border {{ request()->routeIs('reports.due-date') ? 'bg-indigo-600 text-white border-indigo-600 shadow-indigo-200 transform scale-105' : 'bg-white text-gray-500 border-gray-200 hover:border-indigo-300 hover:text-indigo-600 hover:shadow-md' }}">
            Due Dates
        </a>
        <a href="{{ route('reports.staff-productivity', request()->query()) }}" class="px-5 py-2.5 text-base font-bold rounded-full transition-all duration-200 shadow-sm border {{ request()->routeIs('reports.staff-productivity') ? 'bg-indigo-600 text-white border-indigo-600 shadow-indigo-200 transform scale-105' : 'bg-white text-gray-500 border-gray-200 hover:border-indigo-300 hover:text-indigo-600 hover:shadow-md' }}">
            Staff Productivity
        </a>
        <a href="{{ route('reports.client-profitability', request()->query()) }}" class="px-5 py-2.5 text-base font-bold rounded-full transition-all duration-200 shadow-sm border {{ request()->routeIs('reports.client-profitability') ? 'bg-indigo-600 text-white border-indigo-600 shadow-indigo-200 transform scale-105' : 'bg-white text-gray-500 border-gray-200 hover:border-indigo-300 hover:text-indigo-600 hover:shadow-md' }}">
            Profitability
        </a>
    </div>
</div>