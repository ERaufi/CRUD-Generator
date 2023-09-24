<?php

namespace Eraufi\Crud;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str; // Import the Str class
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;


use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class GenerateCRUDCommand extends Command
{
    protected $signature = 'crud:generate {model}';

    protected $description = 'Generate CRUD operations for a model based on the database table';


    protected $tableName;

    public function handle()
    {
        $model = $this->argument('model');
        $this->tableName = Str::snake($model);

        // Check if the table exists
        if (!Schema::hasTable($this->tableName)) {
            // Table doesn't exist, add 's' to the end of the table name
            $this->tableName .= 's';
            $this->info("Table '$this->tableName' doesn't exist, appended 's' to the table name.");
        }

        $question = [
            'generateView' => $this->confirm('Generate Views?'),
            'isEducationl' => $this->confirm('Are you Using this for Educational Purposes? (Adds Comments)'),
        ];

        $columns = Schema::getColumnListing($this->tableName);

        $this->requestCodes($model, $columns, $question);
        $this->controllerCodes($model, $columns, $question);

        $this->routeCodes($model, $question);
        if ($question['generateView']) {
            $this->indexViewCodes($model, $columns);
            $this->createViewCodes($model);
            $this->createEditCodes($model);
            $this->createShowCodes($model);
        }
    }


    // Start Validation Codes==========================================================================
    public function requestCodes($model, $columns, $question)
    {
        // Generate the code for the request class
        $codes = "<?php\n";
        $codes .= "namespace App\Http\Requests;\n";
        $codes .= "use Illuminate\Foundation\Http\FormRequest;\n";
        $codes .= "class {$model}Request extends FormRequest\n";
        $codes .= "{\n";
        $codes .= "    // Authorize the request\n";
        $codes .= "    public function authorize(): bool\n";
        $codes .= "    {\n";
        $codes .= "        return true;\n";
        $codes .= "    }\n";
        $codes .= "    // Define the validation rules\n";
        $codes .= "    public function rules(): array\n";
        $codes .= "    {\n";
        $codes .= "        return [\n";

        // Loop through columns and add validation rules
        foreach ($columns as $column) {
            if ($column != 'id' && $column != 'created_at' && $column != 'updated_at') {
                $validationComment = $question['isEducationl'] ? " // Add validation rule for $column" : "";
                $codes .= "            '$column' => ['required'],$validationComment\n";
            }
        }
        $codes .= "        ];\n";
        $codes .= "    }\n";
        $codes .= "}\n";

        // Create the directory if it doesn't exist
        $directoryPath = base_path("app/Http/Requests/");
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        // Write the code to the request file
        $controllerFilePath = base_path("app/Http/Requests/{$model}Request.php");
        file_put_contents($controllerFilePath, $codes);

        // Inform the user about the successful addition of validations
        $this->info("Validations for $model added successfully.");
    }
    // Start Validation Codes==========================================================================

    // Start Controller Codes=========================================================================
    public function controllerCodes($model, $columns, $question)
    {
        $controllerCode = "<?php\n\nnamespace App\Http\Controllers;\n\n";
        $controllerCode .= "use App\Models\\$model;\n";
        $controllerCode .= "use Illuminate\Http\Request;\n\n";
        $controllerCode .= "use App\Http\Requests\\{$model}Request;;\n\n";

        $controllerCode .= "class {$model}Controller extends Controller\n{\n";

        // Add index method
        $controllerCode .= "    public function index(Request \$request)\n";
        $controllerCode .= "    {\n";
        $controllerCode .= $question['isEducationl'] ? "        // Retrieve all $model data\n" : "";
        $controllerCode .= "        \$datas = $model::all();\n";
        $controllerCode .= "        return view('{$model}.index', compact('datas'));\n";
        $controllerCode .= "    }\n\n";

        // Add store method
        $controllerCode .= "    public function store({$model}Request \$request)\n    {\n";
        $controllerCode .= "        // Create a new $model instance\n";
        $controllerCode .= "        \$item = new $model();\n";
        foreach ($columns as $column) {
            if ($column != 'id' && $column != 'created_at' && $column != 'updated_at') {
                $controllerCode .= $question['isEducationl'] ? "        // Set $column from the request data\n" : "";
                $controllerCode .= "        \$item->$column = \$request->$column;\n";
            }
        }
        $controllerCode .= "        \$item->save();\n";
        $controllerCode .= "        return redirect('{$model}')->with('success', '{$model} created successfully.');\n";
        $controllerCode .= "    }\n\n";

        // Add show method
        $controllerCode .= "    public function show(\$id)\n    {\n";
        $controllerCode .= $question['isEducationl'] ? "        // Find and retrieve a $model instance by ID\n" : "";
        $controllerCode .= "        \$item = {$model}::findOrFail(\$id);\n";
        $controllerCode .= "        return view('{$model}.show', compact('item'));\n";
        $controllerCode .= "    }\n\n";

        // Add edit method
        $controllerCode .= "    public function edit(\$id)\n    {\n";
        $controllerCode .= $question['isEducationl'] ? "        // Find and retrieve a $model instance by ID for editing\n" : "";
        $controllerCode .= "        \$item = {$model}::findOrFail(\$id);\n";
        $controllerCode .= "        return view('{$model}.edit', compact('item'));\n";
        $controllerCode .= "    }\n\n";

        // Add update method
        $controllerCode .= "    public function update({$model}Request \$request, \$id)\n    {\n";
        $controllerCode .= $question['isEducationl'] ? "        // Find and retrieve a $model instance by ID for updating\n" : "";
        $controllerCode .= "        \$item = {$model}::findOrFail(\$id);\n";

        foreach ($columns as $column) {
            if ($column != 'id' && $column != 'created_at' && $column != 'updated_at') {
                $controllerCode .= $question['isEducationl'] ? "        // Update $column from the request data\n" : "";
                $controllerCode .= "        \$item->$column = \$request->$column;\n";
            }
        }
        $controllerCode .= "        \$item->update();\n";
        $controllerCode .= "        return redirect('{$model}')->with('success', '{$model} updated successfully.');\n";
        $controllerCode .= "    }\n\n";

        // Add destroy method
        $controllerCode .= "    public function destroy(\$id)\n    {\n";
        $controllerCode .= $question['isEducationl'] ? "        // Delete the {$model} instance\n" : "";
        $controllerCode .= "        $model::findOrFail(\$id)->delete();\n";
        $controllerCode .= "        return redirect('{$model}')->with('success', '{$model} deleted successfully.');\n";
        $controllerCode .= "    }\n\n";

        $controllerCode .= "}\n";

        $controllerFilePath = app_path("Http/Controllers/{$model}Controller.php");
        file_put_contents($controllerFilePath, $controllerCode);

        $this->info("Controller for $model generated successfully!");
    }
    // Start Controller Codes=========================================================================

    // Start Route Codes===============================================================================
    public function routeCodes($model, $question)
    {
        $controllerName = ucfirst($model) . 'Controller';
        $controllerClass = "App\Http\Controllers\\{$controllerName}::class";
        $routeFilePath = base_path('routes/web.php');

        if (!File::exists($routeFilePath)) {
            $this->error('routes/web.php file does not exist.');
            return;
        }

        $routeCode = "// routes for $model\n";
        $routeCode .= "Route::prefix('$model')->controller($controllerClass)->group(function(){\n";

        $routeCode .= $question['isEducationl'] ? "// GET Route: Display a listing of $model items\n" : "";
        $routeCode .= "Route::get('/', 'index');\n";

        $routeCode .= $question['isEducationl'] ? "// View Route: Display the create $model form\n" : "";
        $routeCode .= "Route::view('/create','$model.create');\n";

        $routeCode .= $question['isEducationl'] ? "// POST Route: Store a newly created $model item in the database. \n" : "";
        $routeCode .= "Route::post('/store','store');\n";

        $routeCode .= $question['isEducationl'] ? "// GET Route: Display the specified $model item. \n" : "";
        $routeCode .= "Route::get('show/{id}','show');\n";

        $routeCode .= $question['isEducationl'] ? "// GET Route: Display the edit $model form. \n" : "";
        $routeCode .= "Route::get('/edit/{id}','edit');\n";

        $routeCode .= $question['isEducationl'] ? "// PUT Route: Update the specified $model item in the database. \n" : "";
        $routeCode .= "Route::put('update/{id}','update');\n";

        $routeCode .= $question['isEducationl'] ? "// DELETE Route: Remove the specified $model item from the database. \n" : "";
        $routeCode .= "Route::delete('destroy/{id}','destroy');\n";
        $routeCode .= "});\n";

        // Check if the routes already exist in web.php to avoid duplication
        $existingRoutes = File::get($routeFilePath);
        if (strpos($existingRoutes, $routeCode) === false) {
            File::append($routeFilePath, "\n" . $routeCode);
            $this->info("CRUD routes for $model added successfully to routes/web.php.");
            flush(); // Ensure immediate output
        } else {
            $this->info("CRUD routes for $model already exist in routes/web.php.");
        }
    }
    // Start Route Codes===============================================================================

    // Start Index Blade Codes=======================================================================
    public function indexViewCodes($model, $columnNames)
    {
        $create = "<!DOCTYPE html>\n";
        $create .= "<html lang='en'>\n";
        $create .= "<head>\n";
        $create .= "    <meta charset='UTF-8'>\n";
        $create .= "    <meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
        $create .= "    <meta http-equiv='X-UA-Compatible' content='ie=edge'>\n";
        $create .= "    <meta name='csrf-token' content='{{ csrf_token() }}'>\n";
        $create .= "    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC' crossorigin='anonymous'>\n";
        $create .= "    <title>{$model}</title>\n";
        $create .= "</head>\n";
        $create .= "<body>\n";
        $create .= "<a href='{{URL('$model/create')}}'>Create</a>\n";
        $create .= "<h1>{$model}</h1>\n";
        $create .= "<table class='table' width='100%'>\n";
        $create .= "    <thead>\n";
        $create .= "        <tr>\n";

        foreach ($columnNames as $columnName) {
            if ($columnName != 'id' && $columnName != 'created_at' && $columnName != 'updated_at') {
                $create .= "            <th>{$columnName}</th>\n";
            }
        }

        $create .= "            <th>Action</th>\n";
        $create .= "        </tr>\n";
        $create .= "    </thead>\n";
        $create .= "    <tbody>\n";
        $create .= "        @foreach(\$datas as \$data)\n";
        $create .= "        <tr>\n";

        foreach ($columnNames as $columnName) {
            if ($columnName != 'id' && $columnName != 'created_at' && $columnName != 'updated_at') {
                $create .= "            <td>{{ \$data->{$columnName} }}</td>\n";
            }
        }

        $create .= "            <td><a href='{{ URL('$model/edit',\$data->id) }}' class='btn btn-primary'>Edit</a>\n";
        $create .= "<a href='{{ URL('$model/show',\$data->id) }}' class='btn btn-primary'>View</a>\n";
        $create .= "                <form action='{{ URL('$model/destroy',\$data->id) }}' method='POST'>\n";
        $create .= "                    @csrf\n";
        $create .= "                    @method('DELETE')\n";
        $create .= "                    <button type='submit' class='btn btn-danger'>Delete</button>\n";
        $create .= "                </form>\n";
        $create .= "            </td>\n";
        $create .= "        </tr>\n";
        $create .= "        @endforeach\n";
        $create .= "    </tbody>\n";
        $create .= "</table>\n";
        $create .= "</body>\n";
        $create .= "</html>\n";

        $directoryPath = base_path("resources/views/{$model}/");

        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        $controllerFilePath = base_path("resources/views/{$model}/index.blade.php");
        file_put_contents($controllerFilePath, $create);

        $this->info("Index View for $model added successfully.");
    }
    // Start Index Blade Codes=======================================================================

    // Start Create blade Codes===================================================================
    public function createViewCodes($model)
    {
        $tableName = Str::snake($model);

        $columnTypes = $this->getColumnTypes($tableName);

        $create = "<!DOCTYPE html>\n";
        $create .= "<html lang='en'>\n";
        $create .= "<head>\n";
        $create .= "    <meta charset='UTF-8'>\n";
        $create .= "    <meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
        $create .= "    <meta http-equiv='X-UA-Compatible' content='ie=edge'>\n";
        $create .= "    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC' crossorigin='anonymous'>\n";
        $create .= "    <title>{$model}</title>\n";
        $create .= "</head>\n";
        $create .= "<body>\n";
        $create .= "<h1>Create {$model}</h1>\n";
        $create .= "<form method='POST' action='{{ URL('$model/store') }}'>\n";
        $create .= "@csrf\n";
        $create .= "<div class='card'>\n";
        $create .= "<div class='card-body'>\n";
        $create .= "<div class='row'>\n";

        foreach ($columnTypes as $columnName => $columnType) {
            if ($columnName != 'id' && $columnName != 'created_at' && $columnName != 'updated_at') {
                $inputField = $this->generateInputField($columnName, $columnType);
                $create .= $inputField;
            }
        }

        $create .= "</div>\n";
        $create .= "</div>\n";
        $create .= "</div>\n";
        $create .= "<button type='submit' class='btn btn-success'>Create</button>\n";
        $create .= "</form>\n";
        $create .= "</body>\n";
        $create .= "</html>\n";
        $directoryPath = base_path("resources/views/{$model}/");

        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        $controllerFilePath = base_path("resources/views/{$model}/create.blade.php");
        file_put_contents($controllerFilePath, $create);

        $this->info("Create View for $model added successfully.");
    }
    // End Create blade Codes===================================================================

    // Start Edit Blade Codes====================================================================
    public function createEditCodes($model)
    {
        // Get the table name based on the model name
        $tableName = Str::snake($model);

        // Get the column types for the table
        $columnTypes = $this->getColumnTypes($tableName);

        // Start with a form in the view
        $edit = "<!DOCTYPE html>\n";
        $edit .= "<html lang='en'>\n";
        $edit .= "<head>\n";
        $edit .= "    <meta charset='UTF-8'>\n";
        $edit .= "    <meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
        $edit .= "    <meta http-equiv='X-UA-Compatible' content='ie=edge'>\n";
        $edit .= "    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC' crossorigin='anonymous'>\n";
        $edit .= "    <title>Edit {$model}</title>\n";
        $edit .= "</head>\n";
        $edit .= "<body>\n";
        $edit .= "<h1>Edit {$model}</h1>\n";
        $edit .= "<form method='POST' action='{{ URL('$model/update',\$item->id) }}'>\n";
        $edit .= "@csrf\n";
        $edit .= "@method('PUT')\n"; // Use the PUT method for updating

        $edit .= "<div class='card'>\n";
        $edit .= "<div class='card-body'>\n";
        $edit .= "<div class='row'>\n";

        // Loop through columns and include input fields based on their types
        foreach ($columnTypes as $columnName => $columnType) {
            // Generate an input field based on the column type
            if ($columnName != 'id' && $columnName != 'created_at' && $columnName != 'updated_at') {
                $inputField = $this->generateInputField($columnName, $columnType, 'Update');
                $edit .= $inputField;
            }

            // Include the input field in the form
        }

        // Close the form
        $edit .= "</div>\n";
        $edit .= "</div>\n";
        $edit .= "</div>\n";
        $edit .= "<button type='submit' class='btn btn-success'>Update</button>\n";
        $edit .= "</form>\n";
        $edit .= "</body>\n";
        $edit .= "</html>\n";

        $directoryPath = base_path("resources/views/{$model}/");

        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        $controllerFilePath = base_path("resources/views/{$model}/edit.blade.php");
        file_put_contents($controllerFilePath, $edit);

        $this->info("Edit View for $model added successfully.");
    }
    // End Edit Blade Codes====================================================================

    // Start Show Blade Codes====================================================================
    public function createShowCodes($model)
    {
        $tableName = Str::snake($model);

        $columnTypes = $this->getColumnTypes($tableName);

        $create = "<!DOCTYPE html>\n";
        $create .= "<html lang='en'>\n";
        $create .= "<head>\n";
        $create .= "    <meta charset='UTF-8'>\n";
        $create .= "    <meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
        $create .= "    <meta http-equiv='X-UA-Compatible' content='ie=edge'>\n";
        $create .= "    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC' crossorigin='anonymous'>\n";
        $create .= "    <title>Show {$model}</title>\n";
        $create .= "</head>\n";
        $create .= "<body>\n";
        $create .= "<h1>Show {$model}</h1>\n";

        // Assuming you have a $item variable that contains the record to display
        $create .= "<table class='table'>\n";
        $create .= "    <thead>\n";
        $create .= "        <tr>\n";

        foreach ($columnTypes as $columnName => $columnType) {
            if ($columnName != 'id' && $columnName != 'created_at' && $columnName != 'updated_at') {
                $labelText = ucwords(str_replace('_', ' ', $columnName));
                $create .= "            <th>{$labelText}</th>\n";
            }
        }

        $create .= "        </tr>\n";
        $create .= "    </thead>\n";
        $create .= "    <tbody>\n";
        $create .= "        <tr>\n";

        foreach ($columnTypes as $columnName => $columnType) {
            if ($columnName != 'id' && $columnName != 'created_at' && $columnName != 'updated_at') {
                $create .= "            <td>{{ \$item->{$columnName} }}</td>\n";
            }
        }

        $create .= "        </tr>\n";
        $create .= "    </tbody>\n";
        $create .= "</table>\n";
        $create .= "</body>\n";
        $create .= "</html>\n";

        $directoryPath = base_path("resources/views/{$model}/");

        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        $controllerFilePath = base_path("resources/views/{$model}/show.blade.php");
        file_put_contents($controllerFilePath, $create);

        $this->info("Show View for $model added successfully.");
    }
    // Start Show Blade Codes====================================================================

    private function generateInputField($columnName, $columnType, $addOrUpdate = 'add')
    {
        // Define an array of column types and their corresponding HTML input types
        $columnTypeMappings = [
            'string' => 'text',
            'text' => 'textarea',
            'integer' => 'number',
            'int(11)' => 'number',
            'decimal(8,2)' => 'number',
            'decimal' => 'number',
            'boolean' => 'checkbox',
            'date' => 'date',
            'datetime' => 'datetime-local',
            'timestamp' => 'date',
            'email' => 'email', // Example: Adding 'email' type
            'password' => 'password', // Example: Adding 'password' type
        ];

        // Determine the HTML input type based on the column type
        $inputType = $columnTypeMappings[$columnType] ?? 'text';

        // Format the label text (replace underscores with spaces and capitalize words)
        $labelText = ucwords(str_replace('_', ' ', $columnName));

        // Generate the input field HTML
        $inputField = "<div class='col-md-3'>\n";
        $inputField .= "<label for='{$columnName}'>{$labelText}</label>\n";
        $inputField .= "<input type='{$inputType}' name='{$columnName}' id='{$columnName}' class='form-control'\n";
        if ($addOrUpdate == 'Update') {
            $inputField .= " value='{{ old('$columnName', \$item->$columnName) }}'/>\n";
        } else {
            $inputField .= " value='{{ old('$columnName')}}'/>\n";
        }
        $inputField .= "</div>\n";

        return $inputField;
    }

    public function getColumnTypes()
    {
        // Get the column types for the specified table
        $tableName = $this->tableName;
        $query = "SHOW COLUMNS FROM $tableName";
        try {
            $columns = DB::select($query);
        } catch (\Illuminate\Database\QueryException $e) {
            $this->error("Table '$tableName' not found.");
            return [];
        }

        $columnTypes = [];

        foreach ($columns as $column) {
            $columnName = $column->Field;
            $columnType = $column->Type;
            $columnTypes[$columnName] = $columnType;
        }

        return $columnTypes;
    }
}
<?php

namespace Eraufi\Crud;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str; // Import the Str class
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;


use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class GenerateCRUDCommand extends Command
{
    protected $signature = 'crud:generate {model}';

    protected $description = 'Generate CRUD operations for a model based on the database table';


    protected $tableName;


    public function handle()
    {
        $model = $this->argument('model');
        $this->tableName = Str::snake($model);

        // Check if the table exists with the model name
        if (!Schema::hasTable($this->tableName)) {
            // Add 's' to the end of the model name
            $this->tableName .= 's';

            // Check if the table exists with 's' appended
            if (!Schema::hasTable($this->tableName)) {
                // Table doesn't exist, ask the user for the table name
                $this->info("Table '$this->tableName' doesn't exist.");
                $this->tableName = $this->ask('Enter the table name:');
            }
        }

        $question = [
            'generateView' => $this->confirm('Generate Views?'),
            'isEducationl' => $this->confirm('Are you Using this for Educational Purposes? (Adds Comments)'),
        ];

        $columns = Schema::getColumnListing($this->tableName);

        $this->requestCodes($model, $columns, $question);
        $this->controllerCodes($model, $columns, $question);

        $this->routeCodes($model, $question);
        if ($question['generateView']) {
            $this->indexViewCodes($model, $columns);
            $this->createViewCodes($model);
            $this->createEditCodes($model);
            $this->createShowCodes($model);
        }
    }


    // Start Validation Codes==========================================================================
    public function requestCodes($model, $columns, $question)
    {
        // Generate the code for the request class
        $codes = "<?php\n";
        $codes .= "namespace App\Http\Requests;\n";
        $codes .= "use Illuminate\Foundation\Http\FormRequest;\n";
        $codes .= "class {$model}Request extends FormRequest\n";
        $codes .= "{\n";
        $codes .= "    // Authorize the request\n";
        $codes .= "    public function authorize(): bool\n";
        $codes .= "    {\n";
        $codes .= "        return true;\n";
        $codes .= "    }\n";
        $codes .= "    // Define the validation rules\n";
        $codes .= "    public function rules(): array\n";
        $codes .= "    {\n";
        $codes .= "        return [\n";

        // Loop through columns and add validation rules
        foreach ($columns as $column) {
            if ($column != 'id' && $column != 'created_at' && $column != 'updated_at') {
                $validationComment = $question['isEducationl'] ? " // Add validation rule for $column" : "";
                $codes .= "            '$column' => ['required'],$validationComment\n";
            }
        }
        $codes .= "        ];\n";
        $codes .= "    }\n";
        $codes .= "}\n";

        // Create the directory if it doesn't exist
        $directoryPath = base_path("app/Http/Requests/");
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        // Write the code to the request file
        $controllerFilePath = base_path("app/Http/Requests/{$model}Request.php");
        file_put_contents($controllerFilePath, $codes);

        // Inform the user about the successful addition of validations
        $this->info("Validations for $model added successfully.");
    }
    // Start Validation Codes==========================================================================

    // Start Controller Codes=========================================================================
    public function controllerCodes($model, $columns, $question)
    {
        $controllerCode = "<?php\n\nnamespace App\Http\Controllers;\n\n";
        $controllerCode .= "use App\Models\\$model;\n";
        $controllerCode .= "use Illuminate\Http\Request;\n\n";
        $controllerCode .= "use App\Http\Requests\\{$model}Request;;\n\n";

        $controllerCode .= "class {$model}Controller extends Controller\n{\n";

        // Add index method
        $controllerCode .= "    public function index(Request \$request)\n";
        $controllerCode .= "    {\n";
        $controllerCode .= $question['isEducationl'] ? "        // Retrieve all $model data\n" : "";
        $controllerCode .= "        \$datas = $model::all();\n";
        $controllerCode .= "        return view('{$model}.index', compact('datas'));\n";
        $controllerCode .= "    }\n\n";

        // Add store method
        $controllerCode .= "    public function store({$model}Request \$request)\n    {\n";
        $controllerCode .= "        // Create a new $model instance\n";
        $controllerCode .= "        \$item = new $model();\n";
        foreach ($columns as $column) {
            if ($column != 'id' && $column != 'created_at' && $column != 'updated_at') {
                $controllerCode .= $question['isEducationl'] ? "        // Set $column from the request data\n" : "";
                $controllerCode .= "        \$item->$column = \$request->$column;\n";
            }
        }
        $controllerCode .= "        \$item->save();\n";
        $controllerCode .= "        return redirect('{$model}')->with('success', '{$model} created successfully.');\n";
        $controllerCode .= "    }\n\n";

        // Add show method
        $controllerCode .= "    public function show(\$id)\n    {\n";
        $controllerCode .= $question['isEducationl'] ? "        // Find and retrieve a $model instance by ID\n" : "";
        $controllerCode .= "        \$item = {$model}::findOrFail(\$id);\n";
        $controllerCode .= "        return view('{$model}.show', compact('item'));\n";
        $controllerCode .= "    }\n\n";

        // Add edit method
        $controllerCode .= "    public function edit(\$id)\n    {\n";
        $controllerCode .= $question['isEducationl'] ? "        // Find and retrieve a $model instance by ID for editing\n" : "";
        $controllerCode .= "        \$item = {$model}::findOrFail(\$id);\n";
        $controllerCode .= "        return view('{$model}.edit', compact('item'));\n";
        $controllerCode .= "    }\n\n";

        // Add update method
        $controllerCode .= "    public function update({$model}Request \$request, \$id)\n    {\n";
        $controllerCode .= $question['isEducationl'] ? "        // Find and retrieve a $model instance by ID for updating\n" : "";
        $controllerCode .= "        \$item = {$model}::findOrFail(\$id);\n";

        foreach ($columns as $column) {
            if ($column != 'id' && $column != 'created_at' && $column != 'updated_at') {
                $controllerCode .= $question['isEducationl'] ? "        // Update $column from the request data\n" : "";
                $controllerCode .= "        \$item->$column = \$request->$column;\n";
            }
        }
        $controllerCode .= "        \$item->update();\n";
        $controllerCode .= "        return redirect('{$model}')->with('success', '{$model} updated successfully.');\n";
        $controllerCode .= "    }\n\n";

        // Add destroy method
        $controllerCode .= "    public function destroy(\$id)\n    {\n";
        $controllerCode .= $question['isEducationl'] ? "        // Delete the {$model} instance\n" : "";
        $controllerCode .= "        $model::findOrFail(\$id)->delete();\n";
        $controllerCode .= "        return redirect('{$model}')->with('success', '{$model} deleted successfully.');\n";
        $controllerCode .= "    }\n\n";

        $controllerCode .= "}\n";

        $controllerFilePath = app_path("Http/Controllers/{$model}Controller.php");
        file_put_contents($controllerFilePath, $controllerCode);

        $this->info("Controller for $model generated successfully!");
    }
    // Start Controller Codes=========================================================================

    // Start Route Codes===============================================================================
    public function routeCodes($model, $question)
    {
        $controllerName = ucfirst($model) . 'Controller';
        $controllerClass = "App\Http\Controllers\\{$controllerName}::class";
        $routeFilePath = base_path('routes/web.php');

        if (!File::exists($routeFilePath)) {
            $this->error('routes/web.php file does not exist.');
            return;
        }

        $routeCode = "// routes for $model\n";
        $routeCode .= "Route::prefix('$model')->controller($controllerClass)->group(function(){\n";

        $routeCode .= $question['isEducationl'] ? "// GET Route: Display a listing of $model items\n" : "";
        $routeCode .= "Route::get('/', 'index');\n";

        $routeCode .= $question['isEducationl'] ? "// View Route: Display the create $model form\n" : "";
        $routeCode .= "Route::view('/create','$model.create');\n";

        $routeCode .= $question['isEducationl'] ? "// POST Route: Store a newly created $model item in the database. \n" : "";
        $routeCode .= "Route::post('/store','store');\n";

        $routeCode .= $question['isEducationl'] ? "// GET Route: Display the specified $model item. \n" : "";
        $routeCode .= "Route::get('show/{id}','show');\n";

        $routeCode .= $question['isEducationl'] ? "// GET Route: Display the edit $model form. \n" : "";
        $routeCode .= "Route::get('/edit/{id}','edit');\n";

        $routeCode .= $question['isEducationl'] ? "// PUT Route: Update the specified $model item in the database. \n" : "";
        $routeCode .= "Route::put('update/{id}','update');\n";

        $routeCode .= $question['isEducationl'] ? "// DELETE Route: Remove the specified $model item from the database. \n" : "";
        $routeCode .= "Route::delete('destroy/{id}','destroy');\n";
        $routeCode .= "});\n";

        // Check if the routes already exist in web.php to avoid duplication
        $existingRoutes = File::get($routeFilePath);
        if (strpos($existingRoutes, $routeCode) === false) {
            File::append($routeFilePath, "\n" . $routeCode);
            $this->info("CRUD routes for $model added successfully to routes/web.php.");
            flush(); // Ensure immediate output
        } else {
            $this->info("CRUD routes for $model already exist in routes/web.php.");
        }
    }
    // Start Route Codes===============================================================================

    // Start Index Blade Codes=======================================================================
    public function indexViewCodes($model, $columnNames)
    {
        $create = "<!DOCTYPE html>\n";
        $create .= "<html lang='en'>\n";
        $create .= "<head>\n";
        $create .= "    <meta charset='UTF-8'>\n";
        $create .= "    <meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
        $create .= "    <meta http-equiv='X-UA-Compatible' content='ie=edge'>\n";
        $create .= "    <meta name='csrf-token' content='{{ csrf_token() }}'>\n";
        $create .= "    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC' crossorigin='anonymous'>\n";
        $create .= "    <title>{$model}</title>\n";
        $create .= "</head>\n";
        $create .= "<body>\n";
        $create .= "<a href='{{URL('$model/create')}}'>Create</a>\n";
        $create .= "<h1>{$model}</h1>\n";
        $create .= "<table class='table' width='100%'>\n";
        $create .= "    <thead>\n";
        $create .= "        <tr>\n";

        foreach ($columnNames as $columnName) {
            if ($columnName != 'id' && $columnName != 'created_at' && $columnName != 'updated_at') {
                $create .= "            <th>{$columnName}</th>\n";
            }
        }

        $create .= "            <th>Action</th>\n";
        $create .= "        </tr>\n";
        $create .= "    </thead>\n";
        $create .= "    <tbody>\n";
        $create .= "        @foreach(\$datas as \$data)\n";
        $create .= "        <tr>\n";

        foreach ($columnNames as $columnName) {
            if ($columnName != 'id' && $columnName != 'created_at' && $columnName != 'updated_at') {
                $create .= "            <td>{{ \$data->{$columnName} }}</td>\n";
            }
        }

        $create .= "            <td><a href='{{ URL('$model/edit',\$data->id) }}' class='btn btn-primary'>Edit</a>\n";
        $create .= "<a href='{{ URL('$model/show',\$data->id) }}' class='btn btn-primary'>View</a>\n";
        $create .= "                <form action='{{ URL('$model/destroy',\$data->id) }}' method='POST'>\n";
        $create .= "                    @csrf\n";
        $create .= "                    @method('DELETE')\n";
        $create .= "                    <button type='submit' class='btn btn-danger'>Delete</button>\n";
        $create .= "                </form>\n";
        $create .= "            </td>\n";
        $create .= "        </tr>\n";
        $create .= "        @endforeach\n";
        $create .= "    </tbody>\n";
        $create .= "</table>\n";
        $create .= "</body>\n";
        $create .= "</html>\n";

        $directoryPath = base_path("resources/views/{$model}/");

        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        $controllerFilePath = base_path("resources/views/{$model}/index.blade.php");
        file_put_contents($controllerFilePath, $create);

        $this->info("Index View for $model added successfully.");
    }
    // Start Index Blade Codes=======================================================================

    // Start Create blade Codes===================================================================
    public function createViewCodes($model)
    {
        $tableName = Str::snake($model);

        $columnTypes = $this->getColumnTypes($tableName);

        $create = "<!DOCTYPE html>\n";
        $create .= "<html lang='en'>\n";
        $create .= "<head>\n";
        $create .= "    <meta charset='UTF-8'>\n";
        $create .= "    <meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
        $create .= "    <meta http-equiv='X-UA-Compatible' content='ie=edge'>\n";
        $create .= "    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC' crossorigin='anonymous'>\n";
        $create .= "    <title>{$model}</title>\n";
        $create .= "</head>\n";
        $create .= "<body>\n";
        $create .= "<h1>Create {$model}</h1>\n";
        $create .= "<form method='POST' action='{{ URL('$model/store') }}'>\n";
        $create .= "@csrf\n";
        $create .= "<div class='card'>\n";
        $create .= "<div class='card-body'>\n";
        $create .= "<div class='row'>\n";

        foreach ($columnTypes as $columnName => $columnType) {
            if ($columnName != 'id' && $columnName != 'created_at' && $columnName != 'updated_at') {
                $inputField = $this->generateInputField($columnName, $columnType);
                $create .= $inputField;
            }
        }

        $create .= "</div>\n";
        $create .= "</div>\n";
        $create .= "</div>\n";
        $create .= "<button type='submit' class='btn btn-success'>Create</button>\n";
        $create .= "</form>\n";
        $create .= "</body>\n";
        $create .= "</html>\n";
        $directoryPath = base_path("resources/views/{$model}/");

        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        $controllerFilePath = base_path("resources/views/{$model}/create.blade.php");
        file_put_contents($controllerFilePath, $create);

        $this->info("Create View for $model added successfully.");
    }
    // End Create blade Codes===================================================================

    // Start Edit Blade Codes====================================================================
    public function createEditCodes($model)
    {
        // Get the table name based on the model name
        $tableName = Str::snake($model);

        // Get the column types for the table
        $columnTypes = $this->getColumnTypes($tableName);

        // Start with a form in the view
        $edit = "<!DOCTYPE html>\n";
        $edit .= "<html lang='en'>\n";
        $edit .= "<head>\n";
        $edit .= "    <meta charset='UTF-8'>\n";
        $edit .= "    <meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
        $edit .= "    <meta http-equiv='X-UA-Compatible' content='ie=edge'>\n";
        $edit .= "    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC' crossorigin='anonymous'>\n";
        $edit .= "    <title>Edit {$model}</title>\n";
        $edit .= "</head>\n";
        $edit .= "<body>\n";
        $edit .= "<h1>Edit {$model}</h1>\n";
        $edit .= "<form method='POST' action='{{ URL('$model/update',\$item->id) }}'>\n";
        $edit .= "@csrf\n";
        $edit .= "@method('PUT')\n"; // Use the PUT method for updating

        $edit .= "<div class='card'>\n";
        $edit .= "<div class='card-body'>\n";
        $edit .= "<div class='row'>\n";

        // Loop through columns and include input fields based on their types
        foreach ($columnTypes as $columnName => $columnType) {
            // Generate an input field based on the column type
            if ($columnName != 'id' && $columnName != 'created_at' && $columnName != 'updated_at') {
                $inputField = $this->generateInputField($columnName, $columnType, 'Update');
                $edit .= $inputField;
            }

            // Include the input field in the form
        }

        // Close the form
        $edit .= "</div>\n";
        $edit .= "</div>\n";
        $edit .= "</div>\n";
        $edit .= "<button type='submit' class='btn btn-success'>Update</button>\n";
        $edit .= "</form>\n";
        $edit .= "</body>\n";
        $edit .= "</html>\n";

        $directoryPath = base_path("resources/views/{$model}/");

        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        $controllerFilePath = base_path("resources/views/{$model}/edit.blade.php");
        file_put_contents($controllerFilePath, $edit);

        $this->info("Edit View for $model added successfully.");
    }
    // End Edit Blade Codes====================================================================

    // Start Show Blade Codes====================================================================
    public function createShowCodes($model)
    {
        $tableName = Str::snake($model);

        $columnTypes = $this->getColumnTypes($tableName);

        $create = "<!DOCTYPE html>\n";
        $create .= "<html lang='en'>\n";
        $create .= "<head>\n";
        $create .= "    <meta charset='UTF-8'>\n";
        $create .= "    <meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
        $create .= "    <meta http-equiv='X-UA-Compatible' content='ie=edge'>\n";
        $create .= "    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC' crossorigin='anonymous'>\n";
        $create .= "    <title>Show {$model}</title>\n";
        $create .= "</head>\n";
        $create .= "<body>\n";
        $create .= "<h1>Show {$model}</h1>\n";

        // Assuming you have a $item variable that contains the record to display
        $create .= "<table class='table'>\n";
        $create .= "    <thead>\n";
        $create .= "        <tr>\n";

        foreach ($columnTypes as $columnName => $columnType) {
            if ($columnName != 'id' && $columnName != 'created_at' && $columnName != 'updated_at') {
                $labelText = ucwords(str_replace('_', ' ', $columnName));
                $create .= "            <th>{$labelText}</th>\n";
            }
        }

        $create .= "        </tr>\n";
        $create .= "    </thead>\n";
        $create .= "    <tbody>\n";
        $create .= "        <tr>\n";

        foreach ($columnTypes as $columnName => $columnType) {
            if ($columnName != 'id' && $columnName != 'created_at' && $columnName != 'updated_at') {
                $create .= "            <td>{{ \$item->{$columnName} }}</td>\n";
            }
        }

        $create .= "        </tr>\n";
        $create .= "    </tbody>\n";
        $create .= "</table>\n";
        $create .= "</body>\n";
        $create .= "</html>\n";

        $directoryPath = base_path("resources/views/{$model}/");

        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        $controllerFilePath = base_path("resources/views/{$model}/show.blade.php");
        file_put_contents($controllerFilePath, $create);

        $this->info("Show View for $model added successfully.");
    }
    // Start Show Blade Codes====================================================================

    private function generateInputField($columnName, $columnType, $addOrUpdate = 'add')
    {
        // Define a mapping for column types to HTML input types
        $columnTypeMappings = [
            'string' => 'text',
            'text' => 'textarea',
            'integer' => 'number',
            'decimal' => 'number',
            'boolean' => 'checkbox',
            'date' => 'date',
            'datetime' => 'datetime-local',
            'timestamp' => 'date',
            'email' => 'email',
            'password' => 'password',
        ];

        // Remove any brackets from the column type (e.g., int(11) => int, decimal(8,2) => decimal)
        $columnType = preg_replace('/\([^)]*\)/', '', $columnType);

        // Determine the HTML input type based on the column type
        $inputType = $columnTypeMappings[$columnType] ?? 'text';

        // Format the label text (replace underscores with spaces and capitalize words)
        $labelText = ucwords(str_replace('_', ' ', $columnName));

        // Generate the input field HTML
        $inputField = "<div class='col-md-3'>\n";
        $inputField .= "<label for='{$columnName}'>{$labelText}</label>\n";
        $inputField .= "<input type='{$inputType}' name='{$columnName}' id='{$columnName}' class='form-control'\n";
        if ($addOrUpdate == 'Update') {
            $inputField .= " value='{{ old('$columnName', \$item->$columnName) }}'/>\n";
        } else {
            $inputField .= " value='{{ old('$columnName')}}'/>\n";
        }
        $inputField .= "</div>\n";

        return $inputField;
    }


    public function getColumnTypes()
    {
        // Get the column types for the specified table
        $tableName = $this->tableName;
        $query = "SHOW COLUMNS FROM $tableName";
        try {
            $columns = DB::select($query);
        } catch (\Illuminate\Database\QueryException $e) {
            $this->error("Table '$tableName' not found.");
            return [];
        }

        $columnTypes = [];

        foreach ($columns as $column) {
            $columnName = $column->Field;
            $columnType = $column->Type;
            $columnTypes[$columnName] = $columnType;
        }

        return $columnTypes;
    }
}
