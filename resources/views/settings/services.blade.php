@extends('layouts.app')

@section('header')
<div class="flex justify-between items-center w-full">
    <div class="flex items-center gap-4">
        <h2 class="text-xl font-semibold text-gray-800">Service Master Settings</h2>
    </div>
    <button onclick="openModal('create')" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded shadow text-sm">
        + Add New Service
    </button>
</div>
@endsection

@section('content')
<div class="bg-white shadow rounded-lg overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name / Code</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Frequency</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Default Due Date</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach($services as $service)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">{{ $service->name }}</div>
                    <div class="text-xs text-gray-500">{{ $service->code }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                        {{ $service->frequency }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    @if($service->frequency === 'Monthly')
                    {{ $service->due_day }}th of every month
                    @elseif($service->frequency === 'Annually')
                    {{ $service->due_month ? date('F', mktime(0, 0, 0, $service->due_month, 1)) : '-' }} {{ $service->due_day }}
                    @else
                    {{ $service->due_day ? $service->due_day : '-' }}
                    @endif
                </td>
                <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                    {{ $service->description }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button onclick='openModal("edit", @json($service))' class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</button>
                    <!-- Delete Form (Optional, be careful with foreign keys) -->
                    <!-- <form ... -> -->
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Modal -->
<div id="serviceModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="serviceForm" method="POST">
                @csrf
                <div id="methodField"></div>

                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modalTitle">Add Service</h3>

                    <div class="space-y-4">
                        <!-- Name & Code -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                                <input type="text" name="name" id="name" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="code" class="block text-sm font-medium text-gray-700">Code</label>
                                <input type="text" name="code" id="code" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                        </div>

                        <!-- Frequency -->
                        <div>
                            <label for="frequency" class="block text-sm font-medium text-gray-700">Frequency</label>
                            <select name="frequency" id="frequency" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="Monthly">Monthly</option>
                                <option value="Quarterly">Quarterly</option>
                                <option value="Half-Yearly">Half-Yearly</option>
                                <option value="Annually">Annually</option>
                                <option value="One-Time">One-Time</option>
                            </select>
                        </div>

                        <!-- Due Date -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="due_day" class="block text-sm font-medium text-gray-700">Due Day</label>
                                <input type="number" name="due_day" id="due_day" max="31" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="e.g. 20">
                            </div>
                            <div>
                                <label for="due_month" class="block text-sm font-medium text-gray-700">Due Month (for Annual)</label>
                                <select name="due_month" id="due_month" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                    <option value="">-- N/A --</option>
                                    @foreach(range(1, 12) as $m)
                                    <option value="{{ $m }}">{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" id="description" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                        Save
                    </button>
                    <button type="button" onclick="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
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

            // Fill values
            document.getElementById('name').value = service.name;
            document.getElementById('code').value = service.code;
            document.getElementById('frequency').value = service.frequency;
            document.getElementById('due_day').value = service.due_day;
            document.getElementById('due_month').value = service.due_month || '';
            document.getElementById('description').value = service.description;

            // Lock code for edit
            document.getElementById('code').readOnly = true;
            document.getElementById('code').classList.add('bg-gray-100');
        } else {
            title.innerText = 'Add Service';
            form.action = "{{ route('services.store') }}";
            methodField.innerHTML = '';

            // Clear values
            form.reset();
            document.getElementById('code').readOnly = false;
            document.getElementById('code').classList.remove('bg-gray-100');
        }
    }

    function closeModal() {
        document.getElementById('serviceModal').classList.add('hidden');
    }
</script>
@endsection