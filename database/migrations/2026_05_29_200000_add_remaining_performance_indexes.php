<?php

use App\Support\SafeSchema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SafeSchema::addIndex('service_dues', 'client_service_id');
        SafeSchema::addIndex('service_dues', 'due_date');
        SafeSchema::addIndex('service_dues', 'status');
        SafeSchema::addIndex('client_credentials', 'category');
        SafeSchema::addIndex('service_document_requirements', 'service_id');
        SafeSchema::addIndex('client_service_document_checks', 'client_service_id');
        SafeSchema::addIndex('time_entries', 'task_id');
        SafeSchema::addIndex('time_entries', 'user_id');
        SafeSchema::addIndex('time_entries', 'date');
    }

    public function down(): void
    {
        //
    }
};
