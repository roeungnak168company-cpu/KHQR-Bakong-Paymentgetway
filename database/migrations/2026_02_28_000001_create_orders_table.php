<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('md5')->unique();
            $table->string('invoice_id');
            $table->longText('khqr_string');
            $table->longText('items_json');
            $table->integer('total_khr');
            $table->string('currency')->default('KHR');
            $table->string('status')->default('CREATED'); // CREATED|PAID|EXPIRED
            $table->timestamp('expires_at')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->longText('paid_data_json')->nullable();
            $table->timestamp('telegram_notified_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
