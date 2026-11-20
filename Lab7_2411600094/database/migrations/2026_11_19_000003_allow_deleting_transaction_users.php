<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $t) {
            $t->dropForeign(['user_id']); });
        Schema::table('inventory_transactions', function (Blueprint $t) {
            $t->unsignedBigInteger('user_id')->nullable()->change();
            $t->foreign('user_id')->references('id')->on('users')->nullOnDelete(); }); }
    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $t) {
            $t->dropForeign(['user_id']); });
        Schema::table('inventory_transactions', function (Blueprint $t) {
            $t->foreign('user_id')->references('id')->on('users'); }); }
};
