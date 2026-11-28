<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds account/profile fields to the users table for the SIMONGAN
     * Account Settings module. These fields are shared across roles and
     * store the profile photo path plus contact/demographic data.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('must_change_password');
            $table->string('phone')->nullable()->after('avatar');
            $table->string('department')->nullable()->after('phone');
            $table->string('address')->nullable()->after('department');
            $table->enum('gender', ['L', 'P'])->nullable()->after('address');
            $table->date('birth_date')->nullable()->after('gender');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'avatar',
                'phone',
                'department',
                'address',
                'gender',
                'birth_date',
            ]);
        });
    }
};
