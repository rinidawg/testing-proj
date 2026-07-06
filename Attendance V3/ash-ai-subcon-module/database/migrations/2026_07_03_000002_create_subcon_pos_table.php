<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subcon_pos', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();          // SUB-2026-014
            $table->unsignedBigInteger('subcontractor_id');
            $table->string('style');                        // style / design
            $table->unsignedInteger('qty');
            $table->decimal('rate', 10, 2);                 // per-piece rate (per PO)
            $table->date('po_date');
            $table->date('due_date')->nullable();
            $table->string('status', 16)->default('open');  // open | complete
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('subcontractor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subcon_pos');
    }
};
