<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subcon_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subcon_po_id');
            $table->date('delivery_date');
            $table->unsignedInteger('delivered_qty');
            $table->unsignedInteger('accepted_qty');
            $table->unsignedInteger('reject_qty')->default(0);
            // Repairs and scraps are small append-only lists of {qty, date[, reason]}.
            // A rejected piece becomes payable only once it appears here as a repair,
            // attributed to the week of that repair date.
            $table->json('repairs')->nullable();
            $table->json('scraps')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->timestamps();

            $table->index('subcon_po_id');
            $table->index('delivery_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subcon_deliveries');
    }
};
