

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
		if (Schema::hasTable('{{ $table_name }}') && !Schema::hasTable('{{ $new_name }}')) {
        	Schema::rename('{{ $table_name }}', '{{ $new_name }}');
		}
	}
}