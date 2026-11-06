<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_sections', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('slug')->unique();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_section_id')->constrained()->restrictOnDelete();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('slug')->unique();
            $table->text('summary_ar');
            $table->text('summary_en')->nullable();
            $table->longText('details_ar')->nullable();
            $table->longText('details_en')->nullable();
            // Money is stored in baisa (1 OMR = 1000 baisa) to avoid rounding errors.
            $table->unsignedBigInteger('price_minor');
            $table->string('currency', 3)->default('OMR');
            $table->string('duration_ar')->nullable();
            $table->string('duration_en')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('consultation_requests', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable()->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('consultation_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('unpaid')->index();
            $table->string('currency', 3)->default('OMR');
            $table->unsignedBigInteger('subtotal_minor')->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->unsignedBigInteger('tax_minor')->default(0);
            $table->unsignedBigInteger('total_minor')->default(0);
            // Billing details are copied at issue time so later setting changes never alter an issued invoice.
            $table->json('issuer')->nullable();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->text('notes')->nullable();
            $table->date('due_at')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('unit_price_minor');
            $table->unsignedBigInteger('total_minor');
            $table->timestamps();
        });

        Schema::create('billing_settings', function (Blueprint $table) {
            $table->id();
            $table->string('issuer_name')->default('BLACKMAIL.OM');
            $table->text('issuer_address')->nullable();
            $table->string('issuer_email')->nullable();
            $table->string('issuer_phone')->nullable();
            $table->string('tax_number')->nullable();
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->unsignedSmallInteger('due_days')->default(7);
            $table->text('payment_instructions')->nullable();
            $table->timestamps();
        });
        DB::table('billing_settings')->insert(['created_at' => now(), 'updated_at' => now()]);

        foreach ([
            ['invoice_issued', 'إصدار فاتورة', 'فاتورة جديدة رقم {{message}}', "مرحبًا {{name}}،\nصدرت لك فاتورة جديدة رقم {{message}}. يمكنك عرضها وطباعتها ومعرفة طريقة الدفع من حسابك:\n{{account_url}}\nفريق BLACKMAIL.OM"],
            ['invoice_paid', 'تأكيد دفع فاتورة', 'تم استلام دفعة الفاتورة {{message}}', "مرحبًا {{name}}،\nتم تأكيد دفع الفاتورة رقم {{message}}. شكرًا لك. يمكنك عرض الإيصال من حسابك:\n{{account_url}}\nفريق BLACKMAIL.OM"],
        ] as [$key, $name, $subject, $body]) {
            if (! DB::table('email_templates')->where('key', $key)->exists()) {
                DB::table('email_templates')->insert(compact('key', 'name', 'subject', 'body') + ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        DB::table('email_templates')->whereIn('key', ['invoice_issued', 'invoice_paid'])->delete();
        Schema::dropIfExists('billing_settings');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::table('consultation_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_id');
        });
        Schema::dropIfExists('services');
        Schema::dropIfExists('service_sections');
    }
};
