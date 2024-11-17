<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateControlQualityBookDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('control_quality_book_details', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('book_id');
            $table->date('date')->nullable();
            $table->integer('drug_id')->nullable();
            $table->integer('unit_id')->nullable();
            $table->string('number')->nullable();
            $table->date('expire_date')->nullable();
            $table->decimal('quantity', 11, 1)->nullable();
            $table->text('sensory_quality')->nullable()->comment('Chất lượng cảm quan');
            $table->text('conclude')->nullable()->comment('Kết luận');
            $table->text('reason')->nullable()->comment('Lý do kiểm tra');
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
        Schema::dropIfExists('control_quality_book_details');
    }
}
