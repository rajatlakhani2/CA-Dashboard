@extends('layouts.app')

@section('header', 'Service Master Registry')

@section('content')
<div class="space-y-6">
    <!-- Stats Context -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 flex flex-col justify-between">
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Services</p>
                <h3 class="text-3xl font-black text-slate-900 leading-none">{{ $services->count() }}</h3>
            </div>
            <div class="mt-4 flex -space-x-2">
                @foreach($services->take(5) as $s)
                <div class="h-8 w-8 rounded-full bg-slate-100 border-2 border-white flex items-center justify-center text-[10px] font-bold text-slate-400 uppercase">
                    {{ substr($s->code, 0, 2) }}
                </div>
                @endforeach
            </div>
        </div>

        <div class="md:col-span-2 bg-slate-900 rounded-3xl shadow-xl p-8 flex items-center justify-between text-white relative overflow-hidden group">
            <div class="relative z-10 transition-transform duration-500 group-hover:scale-105">
                <h4 class="text-indigo-400 text-[10px] font-black uppercase tracking-[0.3em] mb-2">Registry Control</h4>
                <p class="text-2xl font-black leading-tight">Define Your Practice <br><span class="text-indigo-300">Service Catalog</span></p>
            </div>
            <button onclick="openModal('create')" class="relative z-10 bg-white text-slate-900 px-8 py-3.5 rounded-2xl font-black uppercase text-[10px] tracking-widest hover:bg-indigo-500 hover:text-white transition-all transform active:scale-95 shadow-xl">
                Add Service
            </button>
            <!-- Decorative gradient -->
            <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-500/10 rounded-full -mr-32 -mt-32 blur-3xl"></div>
        </div>

        <div class="bg-indigo-50 rounded-3xl p-6 flex flex-col justify-center">
            <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-1">Statutory Count</p>
            <p class="text-3xl font-black text-indigo-600">{{ $services->where('is_statutory', true)->count() }}</p>
        </div>
    </div>

    <!-- Service List -->
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-10 py-8 border-b border-slate-50 flex items-center justify-between">
            <div>
                <h3 class="font-black text-slate-900 text-xl tracking-tight">Active Catalog</h3>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.2em] mt-1">Configure compliance frequencies and billing codes</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-slate-400 text-[10px] font-black uppercase tracking-widest">
                    <tr>
                        <th class="px-10 py-6">Service Detail</th>
                        <th class="px-10 py-6">Frequency</th>
                        <th class="px-10 py-6">Timeline</th>
                        <th class="px-10 py-6">Description</th>
                        <th class="px-10 py-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($services as $service)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-10 py-7">
                            <div class="flex items-center space-x-4">
                                <div class="h-10 w-10 bg-slate-100 rounded-xl flex items-center justify-center text-xs font-black text-slate-400">
                                    {{ $service->code }}
                                </div>
                                <div>
                                    <div class="text-sm font-black text-slate-900">{{ $service->name }}</div>
                                    @if($service->is_statutory)
                                    <span class="text-[8px] font-black text-orange-500 bg-orange-50 px-1.5 py-0.5 rounded border border-orange-100">STATUTORY</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-10 py-7">
                            <span class="inline-flex px-3 py-1 rounded-lg text-[10px] font-black uppercase bg-indigo-50 text-indigo-700">
                                {{ $service->frequency }}
                            </span>
                        </td>
                        <td class="px-10 py-7">
                            <div class="text-[10px] font-black text-slate-900">
                                @if($service->frequency === 'Monthly')
                                DAY {{ str_pad($service->due_day, 2, '0', STR_PAD_LEFT) }} <span class="text-slate-400">/ MONTH</span>
                                @elseif($service->frequency === 'Annually')
                                {{ strtoupper($service->due_month ? date('M', mktime(0, 0, 0, $service->due_month, 1)) : '-') }} {{ str_pad($service->due_day, 2, '0', STR_PAD_LEFT) }}
                                @else
                                {{ $service->due_day ? 'DAY '.str_pad($service->due_day, 2, '0', STR_PAD_LEFT) : 'FLEXIBLE' }}
                                @endif
                            </div>
                        </td>
                        <td class="px-10 py-7">
                            <p class="text-[10px] text-slate-400 font-medium max-w-xs truncate">{{ $service->description ?? 'No specific instructions set.' }}</p>
                        </td>
                        <td class="px-10 py-7 text-right">
                            <button onclick='openModal("edit", @json($service))' class="text-slate-400 hover:text-indigo-600 transition-colors">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Premium Modal -->
<div id="serviceModal" class="fixed inset-0 z-[100] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>

        <div class="relative bg-white rounded-[2.5rem] shadow-2xl w-full max-w-xl overflow-hidden transform transition-all">
            <div class="absolute top-0 right-0 p-8">
                <button onclick="closeModal()" class="text-slate-400 hover:text-slate-900 transition-colors">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="serviceForm" method="POST" class="p-10">
                @csrf
                <div id="methodField"></div>

                <div class="mb-10">
                    <h3 class="text-2xl font-black text-slate-900 tracking-tight" id="modalTitle">Define Service</h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1 text-indigo-500">Registry Entry</p>
                </div>

                <div class="space-y-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Service Name</label>
                            <input type="text" name="name" id="name" required class="w-full bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 py-4 px-6 font-bold text-slate-900">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Code</label>
                            <input type="text" name="code" id="code" required class="w-full bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 py-4 px-6 font-bold text-slate-900">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Billing Frequency</label>
                        <select name="frequency" id="frequency" class="w-full bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 py-4 px-6 font-bold text-slate-900 appearance-none">
                            <option value="Monthly">Monthly</option>
                            <option value="Quarterly">Quarterly</option>
                            <option value="Half-Yearly">Half-Yearly</option>
                            <option value="Annually">Annually</option>
                            <option value="One-Time">One-Time</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Due Day (1-28)</label>
                            <input type="number" name="due_day" id="due_day" max="31" class="w-full bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 py-4 px-6 font-bold text-slate-900">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Target Month</label>
                            <select name="due_month" id="due_month" class="w-full bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 py-4 px-6 font-bold text-slate-900 appearance-none">
                                <option value="">N/A</option>
                                @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}">{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3 bg-slate-50 p-6 rounded-3xl border border-slate-100 italic">
                        <input type="checkbox" name="is_statutory" id="is_statutory" class="h-5 w-5 rounded-lg border-slate-200 text-indigo-600 focus:ring-indigo-500">
                        <label for="is_statutory" class="text-[10px] font-black text-slate-600 uppercase tracking-widest">Mark as Statutory Compliance</label>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Scope / Notes</label>
                        <textarea name="description" id="description" rows="3" class="w-full bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 py-4 px-6 font-bold text-slate-900"></textarea>
                    </div>
                </div>

                <div class="mt-10 flex space-x-4">
                    <button type="submit" class="flex-1 bg-slate-900 text-white rounded-2xl py-4 font-black uppercase text-xs tracking-[0.2em] transition-all hover:bg-indigo-600 shadow-xl active:scale-95">Save Registry Entry</button>
                    <button type="button" onclick="closeModal()" class="px-8 py-4 text-slate-400 font-black uppercase text-xs tracking-widest hover:text-slate-900 transition-colors">Discard</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openModal(mode, service = null) {
        const modal = document.getElementById('serviceModal');
        const form = document.getElementById('serviceForm');
        const title = document.getElementById('modalTitle');
        const methodField = document.getElementById('methodField');

        modal.classList.remove('hidden');

        if (mode === 'edit' && service) {
            title.innerText = 'Edit Service';
            form.action = `/services/${service.id}`;
            methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';

            document.getElementById('name').value = service.name;
            document.getElementById('code').value = service.code;
            document.getElementById('frequency').value = service.frequency;
            document.getElementById('due_day').value = service.due_day;
            document.getElementById('due_month').value = service.due_month || '';
            document.getElementById('description').value = service.description;
            document.getElementById('is_statutory').checked = !!service.is_statutory;

            document.getElementById('code').readOnly = true;
            document.getElementById('code').classList.add('opacity-50');
        } else {
            title.innerText = 'Define Service';
            form.action = "{{ route('services.store') }}";
            methodField.innerHTML = '';

            form.reset();
            document.getElementById('code').readOnly = false;
            document.getElementById('code').classList.remove('opacity-50');
        }
    }

    function closeModal() {
        document.getElementById('serviceModal').classList.add('hidden');
    }
</script>
@endsection