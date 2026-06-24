<?php

declare(strict_types=1);

namespace HyperfTest\Support;

use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;
use Hyperf\DbConnection\Db;

trait DatabaseSetupTrait
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        Schema::dropIfExists('transfers');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('document')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('type');
            $table->timestamps();
        });

        Schema::create('wallets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->unique();
            $table->bigInteger('balance')->default(0);
            $table->timestamps();
        });

        Schema::create('transfers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('payer_id');
            $table->unsignedBigInteger('payee_id');
            $table->bigInteger('amount');
            $table->string('status');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Db::table('transfers')->truncate();
        Db::table('wallets')->truncate();
        Db::table('users')->truncate();
        parent::tearDown();
    }
}
