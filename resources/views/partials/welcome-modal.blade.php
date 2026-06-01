@if(isset($showWelcomeModal) && $showWelcomeModal)
<div x-data="{ open: true }" x-show="open" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Background backdrop -->
    <div @click="open = false" x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity cursor-pointer"></div>

    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <!-- Modal panel -->
            <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">

                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                        <h3 class="text-base font-semibold leading-6 text-gray-900" id="modal-title">Welcome Back, {{ auth()->user()->name }}!</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 italic">"{{ $positiveThought }}"</p>
                        </div>

                        <div class="mt-4 border-t border-gray-100 pt-4">
                            <h4 class="text-sm font-bold text-gray-700 mb-2">Your Pending Tasks ({{ $myPendingTasks->count() }}):</h4>
                            <div class="bg-gray-50 rounded-md p-3 max-h-60 overflow-y-auto">
                                @if(count($myPendingTasks) > 0)
                                <ul class="space-y-2">
                                    @foreach($myPendingTasks as $task)
                                    <li class="text-sm text-gray-600 flex justify-between border-b border-gray-100 last:border-0 pb-1 last:pb-0">
                                        <span class="truncate pr-2 w-3/4" title="{{ $task->title }}">{{ $task->title }}</span>
                                        <span class="text-xs font-bold whitespace-nowrap {{ $task->due_date->isPast() ? 'text-red-500' : 'text-gray-400' }}">{{ $task->due_date->format('M d') }}</span>
                                    </li>
                                    @endforeach
                                </ul>
                                @else
                                <p class="text-sm text-green-600 text-center">You're all caught up! Great job!</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <button type="button" @click="open = false" class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto">Let's Get Started</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif