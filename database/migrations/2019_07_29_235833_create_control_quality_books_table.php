<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateControlQualityBooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Sổ kiểm soát chất lượng định ký & đột xuất
        Schema::create('control_quality_books', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('drug_store_id');
            $table->string('charge_person')->nullable()->comment('Người phụ trách');
            $table->string('tracking_staff')->nullable()->comment('Nhân viên theo dõi');
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
        Schema::dropIfExists('control_quality_books');
    }
}
