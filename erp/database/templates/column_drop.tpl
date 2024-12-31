

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class {{ $classname }} extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (Schema::hasTable('{{ $table_name }}') 
		&& Schema::hasColumn('{{ $table_name }}', '{{ $field_name }}')) {
			Schema::table('{{ $table_name }}', function (Blueprint $table) {
	            $table->dropColumn('{{ $field_name }}');
	        });
        }
	}
}