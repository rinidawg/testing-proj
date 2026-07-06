<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subcon_payouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subcontractor_id');
            $table->date('week_start');                     // Monday of the pay week
            $table->decimal('amount', 12, 2);
            $table->string('status', 12)->default('paid');  // paid
            $table->date('paid_at')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->timestamps();

            // One payout record per subcontractor per week (idempotent).
            $table->unique(['subcontractor_id', 'week_start'], 'subcon_payout_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subcon_payouts');
    }
};
