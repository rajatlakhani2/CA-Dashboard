<div class="bg-white/80 backdrop-blur-md shadow-sm border border-white/20 rounded-2xl p-6 transition-all duration-300 hover:shadow-lg">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Report Filters</h3>
    </div>
    <form method="GET" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Date Range -->
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Date Range</label>
                <select name="date_range" class="w-full rounded-xl border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-medium bg-white/50">
                    <option value="this_month" {{ request('date_range') == 'this_month' ? 'selected' : '' }}>This Month</option>
                    <option value="last_month" {{ request('date_range') == 'last_month' ? 'selected' : '' }}>Last Month</option>
                    <option value="this_quarter" {{ request('date_range') == 'this_quarter' ? 'selected' : '' }}>This Quarter</option>
                    <option value="this_year" {{ request('date_range') == 'this_year' ? 'selected' : '' }}>This Year</option>
                    <option value="custom" {{ request('date_range') == 'custom' ? 'selected' : '' }}>Custom Range</option>
                </select>
            </div>

            <!-- Start Date -->
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Start Date</label>
                <input type="date" name="start_date" value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}" class="w-full rounded-xl border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-medium bg-white/50">
            </div>

            <!-- End Date -->
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">End Date</label>
                <input type="date" name="end_date" value="{{ request('end_date', now()->endOfMonth()->format('Y-m-d')) }}" class="w-full rounded-xl border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-medium bg-white/50">
            </div>

            <!-- Client Filter -->
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Client</label>
                <select name="client_id" class="w-full rounded-xl border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-medium bg-white/50">
                    <option value="">All Clients</option>
                    @foreach(\App\Models\Client::orderBy('name')->get() as $client)
                    <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex justify-end space-x-3 pt-2">
            <a href="{{ url()->current() }}" class="px-4 py-2 border border-gray-200 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-50 hover:text-gray-800 transition-colors">Clear</a>
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-xl text-sm font-bold hover:bg-indigo-700 shadow-md shadow-indigo-200 transition-all transform active:scale-95">Apply Filters</button>
        </div>
    </form>
</div>