<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBreakCorrectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('break_corrections', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('stamp_correction_request_id');
            $table->foreign('stamp_correction_request_id')->references('id')->on('stamp_correction_requests')->onDelete('cascade');
            $table->datetime('requested_break_start_time')->nullable();
            $table->datetime('requested_break_end_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('break_corrections');
    }
}
