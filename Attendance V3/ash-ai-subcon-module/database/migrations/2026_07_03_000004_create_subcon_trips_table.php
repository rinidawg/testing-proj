<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subcon_trips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subcontractor_id');
            $table->unsignedBigInteger('subcon_po_id')->nullable();
            $table->string('kind', 20);                     // hatid | kuha | extra_kulang | extra_naiwan
            $table->decimal('amount', 10, 2);
            $table->date('trip_date');
            $table->string('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('subcontractor_id');
            $table->index('trip_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subcon_trips');
    }
};
