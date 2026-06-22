<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateTransfersTable extends Migration
{
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('payer_id');
            $table->unsignedBigInteger('payee_id');
            $table->bigInteger('amount');
            $table->string('status');
            $table->timestamps();

            $table->foreign('payer_id')->references('id')->on('users');
            $table->foreign('payee_id')->references('id')->on('users');

            $table->index(['payer_id', 'created_at']);
            $table->index(['payee_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
}
